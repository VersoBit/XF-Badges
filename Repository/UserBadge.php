<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Repository;

use CMTV\Badges\Constants as C;
use CMTV\Badges\XF\Entity\User;
use XF;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Repository;
use XF\Repository\UserAlert;

class UserBadge extends Repository
{
    public function awardWithBadge(User $user, int $badgeId, string $reason = '')
    {
        $reason = trim($reason);

        $inserted = $this->db()->insert(C::_table('user_badge'), [
            'user_id' => $user->user_id,
            'badge_id' => $badgeId,
            'reason' => $reason,
            'award_date' => XF::$time
        ], false, false, 'IGNORE');

        if ($inserted) {
            /** @var UserAlert $alertRepo */
            $alertRepo = $this->repository('XF:UserAlert');
            $alertRepo->alertFromUser($user, $user, 'badge', $badgeId, 'award');

            $params = [
                'user' => $user,
                'reason' => $reason,
                'badge' => $this->finder(C::__('Badge'))->whereId($badgeId)->fetchOne()
            ];

            if (XF::options()->CMTV_Badges_Email_Toggle == 0) {
                $emailOptOut = $this->app()->finder('XF:UserFieldValue')
                    ->where(['user_id', $user->user_id])
                    ->where(['field_id', '=', 'CMTV_Badges_Email_OptOut'])->fetchOne();

                if(!empty($emailOptOut) AND $emailOptOut->field_value == 'a:0:{}' OR empty($emailOptOut)) {
                    $this->app()->mailer()->newMail()
                        ->setToUser($user)
                        ->setTemplate(C::_('badge_award'), $params)
                        ->queue();
                }
            }

            $user->fastUpdate('cmtv_badges_badge_count', $user->cmtv_badges_badge_count + 1);

            return true;
        } else {
            return false;
        }
    }

    public function takeAwayBadge(User $user, int $badgeId)
    {
        $this->db()->delete(C::_table('user_badge'), 'user_id = ? AND badge_id = ?', [
            $user->user_id,
            $badgeId
        ]);

        $this->db()->delete(
            'xf_user_alert', "content_type = 'badge' AND action = 'award' AND content_id = ?",
            $badgeId
        );

        if ($user->cmtv_badges_badge_count > 0) {
            $user->fastUpdate('cmtv_badges_badge_count', $user->cmtv_badges_badge_count - 1);
        }
    }

    public function getRecentUserBadges(User $user)
    {
        $sortSetting = XF::options()->CMTV_Badges_Featured_Badges_Sort;

        if($sortSetting == 'asc' || $sortSetting == 'desc') {
            $finder = $this->finder(C::__('UserBadge'))
                ->where('user_id', $user->user_id)
                ->order('award_date', $sortSetting)
                ->with('Badge')
                ->limit(5);

            $recentBadges = $finder->fetch();
        }
        elseif($sortSetting == 'disabled')
        {
            $recentBadges = null;
        }

        return $recentBadges;
    }

    public function getFeaturedUserBadges(User $user)
    {
        $allowedFeatured = $this->getAllowedFeaturedBadges($user);

        if ($allowedFeatured === 0) {
            return new ArrayCollection([]);
        }

        $finder = $this->finder(C::__('UserBadge'))
            ->where('user_id', $user->user_id)
            ->where('featured', 1)
            ->with('Badge')
            ->order(['Badge.Category.display_order', 'Badge.display_order']);

        if ($allowedFeatured !== -1) {
            $finder->limit($allowedFeatured);
        }

        return $finder->fetch();
    }

    public function getAllowedFeaturedBadges(User $user)
    {
        if (!$user->hasPermission(C::_(), 'manageFeatured')) {
            return 0;
        }

        return $user->hasPermission(C::_(), 'featuredNumber');
    }

    public function getAwardedBadgeIds(int $userId)
    {
        $table = C::_table('user_badge');

        return XF::db()->fetchAllColumn(
            "SELECT `badge_id` FROM {$table} WHERE `user_id` = ?",
            $userId
        );
    }

    public function getUserBadgesData(int $userId)
    {
        $userBadges = $this->finder(C::__('UserBadge'))
            ->where('user_id', $userId)
            ->with('Badge')
            ->order(['Badge.Category.display_order', 'Badge.display_order'])
            ->fetch()
            ->toArray();

        $userBadges = $this->em->getBasicCollection($userBadges);

        $badgeCategories = [];
        $userBadgesOut = [];

        /** @var \CMTV\Badges\Entity\UserBadge $userBadge */
        foreach ($userBadges as $userBadge) {
            $category = $userBadge->Badge->Category;
            $catId = $category ? $category->badge_category_id : 0;
            $badgeId = $userBadge->badge_id;

            if (!array_key_exists($catId, $userBadgesOut)) {
                $badgeCategories[$catId] = $category ?: $this->getBadgeCategoryRepo()->getDefaultCategory();
                $userBadgesOut[$catId] = [];
            }

            $userBadgesOut[$catId][$badgeId] = $userBadge;
        }

        uasort($badgeCategories, function ($a, $b) {
            return $a->display_order <=> $b->display_order;
        });

        return [
            'badgeCategories' => $badgeCategories,
            'userBadges' => $userBadgesOut,
            'totalCategories' => count($badgeCategories),
            'totalBadges' => count($userBadges)
        ];
    }

    public function getUserBadgeCount(int $userId, bool $featured = false)
    {
        $finder = $this->finder(C::__('UserBadge'))->where('user_id', $userId);

        if ($featured) {
            $finder->where('featured', 1);
        }

        return $finder->total();
    }

    //
    // UTIL
    //

    protected function getBadgeCategoryRepo(): BadgeCategory
    {
        return $this->repository(C::__('BadgeCategory'));
    }
}