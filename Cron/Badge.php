<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Cron;

use CMTV\Badges\Constants as C;
use CMTV\Badges\Repository\UserBadge;
use CMTV\Badges\XF\Entity\User;

class Badge
{
    public static function runBadgeCheck()
    {
        /** @var \CMTV\Badges\Repository\Badge $badgeRepo */
        $badgeRepo = \XF::repository(C::__('Badge'));

        $badges = $badgeRepo->findBadgesForList()->fetch();

        if (!$badges)
        {
            return;
        }

        $userFinder = \XF::finder('XF:User');
        //TODO: Add settings to adjust activity filter (or turn it off)
        $users = $userFinder
            ->where('last_activity', '>=', time() - 2 * 3600)
            ->isValidUser(false)
            ->fetch();

        /** @var User $user */
        foreach ($users as $user)
        {
            /** @var UserBadge $userBadgeRepo */
            $userBadgeRepo = \XF::repository(C::__('UserBadge'));

            $awardedIds = $userBadgeRepo->getAwardedBadgeIds($user->user_id);

            /** @var \CMTV\Badges\Entity\Badge $badge */
            foreach ($badges as $badge)
            {
                if (in_array($badge->badge_id, $awardedIds))
                {
                    continue;
                }

                $userCriteria = \XF::app()->criteria('XF:User', $badge->user_criteria);
                $userCriteria->setMatchOnEmpty(false);

                if ($userCriteria->isMatched($user))
                {
                    $userBadgeRepo->awardWithBadge($user, $badge->badge_id);
                }
            }
        }
    }

    protected function getUserBadgeRepo(): UserBadge
    {
        return \XF::repository(C::__('UserBadge'));
    }
}