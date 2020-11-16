<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Job;

use CMTV\Badges\XF\Entity\User;
use XF;
use XF\Job\AbstractRebuildJob;

class BadgeCount extends AbstractRebuildJob
{
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        return $db->fetchAllColumn(
            $db->limit("SELECT `user_id` FROM `xf_user` WHERE `user_id` > ? ORDER BY `user_id`", $batch),
            $start
        );
    }

    protected function rebuildById($id)
    {
        /** @var User $user */
        $user = $this->app->finder('XF:User')->whereId($id)->fetchOne();

        if ($user) {
            $user->fastUpdate('cmtv_badges_badge_count', $user->getBadgeCount());
        }
    }

    protected function getStatusType()
    {
        return XF::phrase('users');
    }
}