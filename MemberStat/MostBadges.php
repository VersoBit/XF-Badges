<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\MemberStat;

use CMTV\Badges\Constants as C;
use XF\Finder\User;
use XF\Entity\MemberStat;

class MostBadges
{
    public static function getBadgeUsers(MemberStat $memberStat, User $finder)
    {
        $finder->order(C::_column('badge_count'), 'DESC');
        $users = $finder->where(C::_column('badge_count'), '>', 0)->limit($memberStat->user_limit)->fetch();

        $results = $users->pluck(function (\XF\Entity\User $user)
        {
            return [$user->user_id, \XF::language()->numberFormat($user->get(C::_column('badge_count')))];
        });

        return $results;
    }
}