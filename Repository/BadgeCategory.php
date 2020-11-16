<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Repository;

use CMTV\Badges\Constants as C;
use XF\Mvc\Entity\Repository;

class BadgeCategory extends Repository
{
    public function getDefaultCategory()
    {
        $category = $this->em->create(C::__('BadgeCategory'));
        $category->setTrusted('badge_category_id', 0);
        $category->setTrusted('display_order', 0);
        $category->setReadOnly(true);

        return $category;
    }

    public function getBadgeCategoriesForList($getDefault = false)
    {
        $categories = $this->finder(C::__('BadgeCategory'))
            ->with('MasterTitle')
            ->order('display_order')
            ->fetch();

        if ($getDefault) {
            $defaultCategory = $this->getDefaultCategory();
            $categories = [$defaultCategory] + $categories->toArray();
        }

        return $categories;
    }

    public function getBadgeCategoryTitlePairs()
    {
        $badgeCategories = $this->finder(C::__('BadgeCategory'))->order('display_order');
        return $badgeCategories->fetch()->pluckNamed('title', 'badge_category_id');
    }
}