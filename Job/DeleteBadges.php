<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Job;

use CMTV\Badges\Constants as C;
use XF;
use XF\Job\AbstractRebuildJob;

class DeleteBadges extends AbstractRebuildJob
{
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        $table = C::_table('badge');
        $categoryId = $this->data['category_id'];

        $result = $db->fetchAllColumn(
            $db->limit("SELECT `badge_id` FROM `{$table}` WHERE `badge_category_id` = {$categoryId}",
                $this->data['batch'],
                $this->data['start']
            )
        );

        return $result;
    }

    protected function rebuildById($id)
    {
        $badge = $this->app->finder(C::__('Badge'))->whereId($id)->fetchOne();
        $badge->delete();
    }

    protected function getStatusType()
    {
        return XF::phrase(C::_('deleting_badges_in_category'));
    }
}