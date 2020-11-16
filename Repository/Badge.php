<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Repository;

use CMTV\Badges\Constants as C;
use XF\Mvc\Entity\Repository;

class Badge extends Repository
{
    public function findBadgesForList()
    {
        return $this->finder(C::__('Badge'))->order('display_order');
    }

    public function getBadgeListData(array $excludeIds = [])
    {
        $badges = $this->findBadgesForList()->fetch()->toArray();
        $badgeCategories = $this->getBadgeCategoryRepo()->getBadgeCategoriesForList(true);

        if ($excludeIds) {
            foreach (array_keys($badges) as $badgeId) {
                if (in_array($badgeId, $excludeIds)) {
                    unset($badges[$badgeId]);
                }
            }
        }

        $badges = $this->em->getBasicCollection($badges);

        return [
            'badgeCategories' => $badgeCategories,
            'badges' => $badges->groupBy('badge_category_id')
        ];
    }

    //
    // UTIL
    //

    protected function getBadgeCategoryRepo(): BadgeCategory
    {
        return $this->repository(C::__('BadgeCategory'));
    }
}