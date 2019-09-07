<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Admin\Controller;

use CMTV\Badges\Constants as C;
use CMTV\Badges\ControllerPlugin\TitleDescription;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete;
use XF\Mvc\ParameterBag;

class BadgeCategory extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission(C::_());
    }

    //
    // ACTIONS
    //

    public function actionIndex()
    {
        return $this->redirectPermanently($this->buildLink('badges'));
    }

    public function actionAdd()
    {
        $badgeCategory = $this->em()->create(C::__('BadgeCategory'));
        return $this->badgeCategoryAddEdit($badgeCategory);
    }

    public function actionEdit(ParameterBag $params)
    {
        $badgeCategory = $this->assertBadgeCategoryExists($params['badge_category_id']);
        return $this->badgeCategoryAddEdit($badgeCategory);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params['badge_category_id'])
        {
            $badgeCategory = $this->assertBadgeCategoryExists($params['badge_category_id']);
        }
        else
        {
            $badgeCategory = $this->em()->create(C::__('BadgeCategory'));
        }

        $this->badgeCategorySaveProcess($badgeCategory)->run();

        return $this->redirect($this->buildLink('badges'));
    }

    public function actionDelete(ParameterBag $params)
    {
        $badgeCategory = $this->assertBadgeCategoryExists($params['badge_category_id']);

        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');

        return $plugin->actionDelete(
            $badgeCategory,
            $this->buildLink('badge-categories/delete', $badgeCategory),
            $this->buildLink('badge-categories/edit', $badgeCategory),
            $this->buildLink('badges'),
            $badgeCategory->title
        );
    }

    public function actionDeleteBadges(ParameterBag $params)
    {
        if ($this->isPost())
        {
            $badgeCategoryId = $params['badge_category_id'];

            $this->app->jobManager()->enqueue(
                C::__('DeleteBadges'),
                ['category_id' => $badgeCategoryId],
                true
            );

            return $this->redirectPermanently($this->buildLink('badges'));
        }
        else
        {
            $badgeCategory = $this->assertBadgeCategoryExists($params['badge_category_id']);

            $totalBadges = $this->finder(C::__('Badge'))
                ->where('badge_category_id', $badgeCategory->badge_category_id)
                ->total();

            $viewParams = [
                'totalBadges' => $totalBadges,
                'badgeCategory' => $badgeCategory
            ];

            return $this->view(
                C::__('BadgeCategory\DeleteBadges'),
                C::_('delete_badges'),
                $viewParams
            );
        }
    }

    //
    // UTIL
    //

    protected function badgeCategoryAddEdit(\CMTV\Badges\Entity\BadgeCategory $badgeCategory)
    {
        $viewParams = [
            'badgeCategory' => $badgeCategory
        ];

        return $this->view(
            C::__('BadgeCategory\Edit'),
            C::_('badge_category_edit'),
            $viewParams
        );
    }

    protected function badgeCategorySaveProcess(\CMTV\Badges\Entity\BadgeCategory $badgeCategory)
    {
        $entityInput = $this->filter([
            'icon_type' => 'str',
            'fa_icon' => 'str',
            'image_url' => 'str',
            'class' => 'str',
            'display_order' => 'uint'
        ]);

        $form = $this->formAction();
        $form->basicEntitySave($badgeCategory, $entityInput);

        /** @var TitleDescription $plugin */
        $plugin = $this->plugin(C::__('TitleDescription'));
        $plugin->saveTitle($form, $badgeCategory);

        return $form;
    }

    protected function assertBadgeCategoryExists($id, $with = null, $phraseKey = null): \CMTV\Badges\Entity\BadgeCategory
    {
        if ($id == 0)
        {
            return $this->getBadgeCategoryRepo()->getDefaultCategory();
        }

        return $this->assertRecordExists(C::__('BadgeCategory'), $id, $with, $phraseKey);
    }

    protected function getBadgeCategoryRepo(): \CMTV\Badges\Repository\BadgeCategory
    {
        return $this->repository(C::__('BadgeCategory'));
    }
}