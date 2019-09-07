<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\XF\Pub\Controller;

use CMTV\Badges\Constants as C;
use CMTV\Badges\Entity\UserBadge;
use CMTV\Badges\Repository\Badge;
use CMTV\Badges\XF\Entity\User;
use XF\Mvc\ParameterBag;

class Member extends XFCP_Member
{
    //
    // ACTIONS
    //

    public function actionBadges(ParameterBag $params)
    {
        /** @var User $user */
        $user = $this->assertViewableUser($params->user_id);

        $userBadgeRepo = $this->getUserBadgeRepo();
        $userBadgesData = $userBadgeRepo->getUserBadgesData($user->user_id);

        $this->refineFeaturedBadges($user);

        if ($user->user_id == \XF::visitor()->user_id)
        {
            // @todo Mark badge award alerts read (see Member pub controller, 'actionTrophies')
        }

        $viewParams = [
            'user' => $user,
            'badgeCategories' => $userBadgesData['badgeCategories'],
            'userBadges' => $userBadgesData['userBadges'],
            'totalCategories' => $userBadgesData['totalCategories'],
            'totalBadges' => $userBadgesData['totalBadges']
        ];

        return $this->view(
            'XF:Member\Badges\Listing',
            C::_('member_badges'),
            $viewParams
        );
    }

    public function actionMarkBadgeFeatured(ParameterBag $params)
    {
        /** @var User $user */
        $user = $this->assertViewableUser($params->user_id);

        if (!$user->canManageFeaturedBadges())
        {
            return $this->noPermission();
        }

        $badgeId = $this->filter('badge_id', 'uint');

        /** @var UserBadge $userBadge */
        $userBadge = $this->finder(C::__('UserBadge'))->whereId([$user->user_id, $badgeId])->fetchOne();

        if (!$userBadge)
        {
            return $this->error(\XF::phrase(C::_('you_cant_feature_this_badge')));
        }

        if ($userBadge->featured)
        {
            $userBadge->fastUpdate('featured', 0);
            return $this->redirectPermanently($this->buildLink('members', $user) . '#badges');
        }

        if (!$user->canAddFeaturedBadge())
        {
            return $this->noPermission(
                \XF::phrase(C::_('you_cant_feature_more_than_x_badges'),
                    ['badgeCount' => $user->hasPermission(C::_(), 'featuredNumber')]
                )
            );
        }

        $userBadge->fastUpdate('featured', 1);

        return $this->redirectPermanently($this->buildLink('members', $user). '#badges');
    }

    public function actionAwardBadge(ParameterBag $params)
    {
        /** @var User $user */
        $user = $this->assertViewableUser($params->user_id);

        if (!$user->canAwardWithBadge())
        {
            return $this->noPermission();
        }

        if ($this->isPost())
        {
            $badgeId = $this->filter('badge_id', 'uint');
            $reason = $this->filter('reason', 'str');

            $this->getUserBadgeRepo()->awardWithBadge($user, $badgeId, $reason);

            return $this->redirectPermanently($this->buildLink('members', $user) . '#badges');
        }
        else
        {
            $userBadgeRepo = $this->getUserBadgeRepo();
            $excludeIds = $userBadgeRepo->getAwardedBadgeIds($user->user_id);

            $viewParams = [
                'user' => $user,
                'badgeData' => $this->getBadgeRepo()->getBadgeListData($excludeIds)
            ];

            return $this->view(
                'XF:Member\Badges\Award',
                C::_('award_with_badge'),
                $viewParams
            );
        }
    }

    public function actionTakeAwayBadge(ParameterBag $params)
    {
        /** @var User $user */
        $user = $this->assertViewableUser($params->user_id);

        if (!$user->canTakeAwayBadge())
        {
            return $this->noPermission();
        }

        if ($this->isPost())
        {
            $badgeId = $this->filter('badge_id', 'uint');

            $this->getUserBadgeRepo()->takeAwayBadge($user, $badgeId);

            return $this->redirectPermanently($this->buildLink('members', $user) . '#badges');
        }
        else
        {
            $viewParams = [
                'user' => $user,
                'userBadgesData' => $this->getUserBadgeRepo()->getUserBadgesData($user->user_id)
            ];

            return $this->view(
                'XF:Member\Badges\TakeAway',
                C::_('take_away_badge'),
                $viewParams
            );
        }
    }

    public function actionEditBadgeReason(ParameterBag $params)
    {
        /** @var User $user */
        $user = $this->assertViewableUser($params->user_id);

        if (!$user->canEditBadgeReason())
        {
            return $this->noPermission();
        }

        $badgeId = $this->filter('badge_id', 'uint');

        /** @var UserBadge $userBadge */
        $userBadge = $this->finder(C::__('UserBadge'))
            ->where('user_id', $user->user_id)
            ->where('badge_id', $badgeId)
            ->fetchOne();

        if (!$userBadge)
        {
            return $this->error(\XF::phrase(C::_('you_cant_change_this_badge_reason')));
        }

        if ($this->isPost())
        {
            $reason = $this->filter('reason', 'str');

            $userBadge->fastUpdate('reason', $reason);

            return $this->redirectPermanently($this->buildLink('members', $user) . '#badges');
        }
        else
        {
            $viewParams = [
                'user' => $user,
                'userBadge' => $userBadge
            ];

            return $this->view(
                'XF:Member\Badges\EditReason',
                C::_('edit_badge_reason'),
                $viewParams
            );
        }
    }

    //
    // UTIL
    //

    protected function refineFeaturedBadges(User $user)
    {
        $allowedNumber = $this->getUserBadgeRepo()->getAllowedFeaturedBadges($user);

        if ($allowedNumber == -1)
        {
            return;
        }

        $fUserBadges = $this->finder(C::__('UserBadge'))
            ->where('user_id', $user->user_id)
            ->where('featured', 1)
            ->order(['Badge.Category.display_order', 'Badge.display_order'])
            ->fetch()
            ->toArray();

        $toUnfeature = array_slice($fUserBadges, $allowedNumber);

        /** @var UserBadge $userBadge */
        foreach ($toUnfeature as $userBadge)
        {
            $userBadge->fastUpdate('featured', 0);
        }
    }

    protected function getUserBadgeRepo(): \CMTV\Badges\Repository\UserBadge
    {
        return $this->repository(C::__('UserBadge'));
    }

    protected function getBadgeRepo(): Badge
    {
        return $this->repository(C::__('Badge'));
    }
}