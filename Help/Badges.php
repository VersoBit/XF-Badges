<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Help;

use CMTV\Badges\Constants as C;
use CMTV\Badges\Repository\Badge;
use XF\Mvc\Controller;
use XF\Mvc\Reply\View;

class Badges
{
    public static function renderBadges(Controller $controller, View &$reply)
    {
        /** @var Badge $badgesRepo */
        $badgesRepo = $controller->repository(C::__('Badge'));

        $badgeData = $badgesRepo->getBadgeListData();

        foreach (array_keys($badgeData['badgeCategories']) as $catId)
        {
            if (!array_key_exists($catId, $badgeData['badges']))
            {
                unset($badgeData['badgeCategories'][$catId]);
            }
        }

        $reply->setParam('badgeData', $badgeData);
    }
}