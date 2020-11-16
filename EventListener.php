<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges;

use CMTV\Badges\Constants as C;
use XF;
use XF\Entity\User;

class EventListener
{
    public static function criteriaUser($rule, array $data, User $user, &$returnValue)
    {
        /** @var XF\Entity\User $user */
        $user = $user;

        switch ($rule) {
            case C::_('badge_count'):
                $returnValue = $user->cmtv_badges_badge_count && $user->cmtv_badges_badge_count >= $data['badges'];
                break;

            case C::_('badge_count_max'):
                $returnValue = $user->cmtv_badges_badge_count && $user->cmtv_badges_badge_count <= $data['badges'];
                break;

            case C::_('has_badge'):
                $ids = explode(',', $data['badge_ids']);

                foreach ($ids as &$id) {
                    $id = intval($id);
                }

                $awardedIds = XF::repository(C::__('UserBadge'))->getAwardedBadgeIds($user->user_id);

                $returnValue = true;

                foreach ($ids as $id) {
                    if (!in_array($id, $awardedIds)) {
                        $returnValue = false;
                    }
                }
                break;
        }
    }
}