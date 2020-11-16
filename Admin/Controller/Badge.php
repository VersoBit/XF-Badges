<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Admin\Controller;

use CMTV\Badges\Constants as C;
use CMTV\Badges\ControllerPlugin\TitleDescription;
use XF;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;

class Badge extends AbstractController
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
        $viewParams = [
            'totalCategories' => 1 + $this->finder(C::__('BadgeCategory'))->total(),
            'totalBadges' => $this->finder(C::__('Badge'))->total(),
            'badgeData' => $this->getBadgeRepo()->getBadgeListData()
        ];

        return $this->view(
            C::__('Badge\Listing'),
            C::_('badge_list'),
            $viewParams
        );
    }

    public function actionAdd()
    {
        $badge = $this->em()->create(C::__('Badge'));
        return $this->badgeAddEdit($badge);
    }

    public function actionEdit(ParameterBag $params)
    {
        $badge = $this->assertBadgeExists($params->badge_id);
        return $this->badgeAddEdit($badge);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params->badge_id) {
            $badge = $this->assertBadgeExists($params->badge_id);
        } else {
            $badge = $this->em()->create(C::__('Badge'));
        }

        $this->badgeSaveProcess($badge)->run();

        return $this->redirect($this->buildLink('badges'));
    }

    public function actionDelete(ParameterBag $params)
    {
        $badge = $this->assertBadgeExists($params->badge_id);

        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');

        return $plugin->actionDelete(
            $badge,
            $this->buildLink('badges/delete', $badge),
            $this->buildLink('badges/edit', $badge),
            $this->buildLink('badges'),
            $badge->title
        );
    }

    public function actionSort(ParameterBag $params)
    {
        if ($this->isPost()) {
            $badges = $this->finder(C::__('Badge'))->fetch();

            foreach ($this->filter('badges', 'array-json-array') as $badgesInCategory) {
                $lastOrder = 0;

                foreach ($badgesInCategory as $key => $badgeValue) {
                    if (!isset($badgeValue['id']) || !isset($badges[$badgeValue['id']])) {
                        continue;
                    }

                    $lastOrder += 10;

                    /** @var \CMTV\Badges\Entity\Badge $badge */
                    $badge = $badges[$badgeValue['id']];
                    $badge->badge_category_id = $badgeValue['parent_id'];
                    $badge->display_order = $lastOrder;
                    $badge->saveIfChanged();
                }
            }

            return $this->redirect($this->buildLink('badges'));
        } else {
            $badgeData = $this->getBadgeRepo()->getBadgeListData();

            $viewParams = [
                'badgeData' => $badgeData
            ];

            return $this->view(
                C::__('Badge\Sort'),
                C::_('badge_sort'),
                $viewParams
            );
        }
    }

    //
    // UTIL
    //

    protected function badgeAddEdit(\CMTV\Badges\Entity\Badge $badge)
    {
        $userCriteria = $this->app->criteria('XF:User', $badge->user_criteria);

        $viewParams = [
            'badge' => $badge,
            'badgeCategories' => $this->getBadgeCategoryRepo()->getBadgeCategoryTitlePairs(),
            'userCriteria' => $userCriteria
        ];

        return $this->view(
            C::__('Badge\Edit'),
            C::_('badge_edit'),
            $viewParams
        );
    }

    protected function badgeSaveProcess(\CMTV\Badges\Entity\Badge $badge)
    {
        $badgeInput = $this->filter([
            'user_criteria' => 'array',
            'icon_type' => 'str',
            'fa_icon' => 'str',
            'image_url' => 'str',
            'class' => 'str',
            'badge_category_id' => 'uint',
            'display_order' => 'uint'
        ]);

        $form = $this->formAction();
        $form->basicEntitySave($badge, $badgeInput);

        /** @var TitleDescription $plugin */
        $plugin = $this->plugin(C::__('TitleDescription'));
        $plugin->saveTitleDescription($form, $badge);

        $form->validate(function (FormAction $form) use ($badgeInput) {
            if (!$badgeInput['icon_type']) {
                $form->logError(XF::phrase(C::_('please_select_the_badge_icon_type')), 'icon_type');
            } else {
                if (!$badgeInput['image_url'] && !$badgeInput['fa_icon']) {
                    $form->logError(XF::phrase(C::_('please_specify_a_badge_icon_value')));
                }
            }
        });

        return $form;
    }

    protected function assertBadgeExists($id, $with = null, $phraseKey = null): \CMTV\Badges\Entity\Badge
    {
        return $this->assertRecordExists(C::__('Badge'), $id, $with, $phraseKey);
    }

    protected function getBadgeRepo(): \CMTV\Badges\Repository\Badge
    {
        return $this->repository(C::__('Badge'));
    }

    protected function getBadgeCategoryRepo(): \CMTV\Badges\Repository\BadgeCategory
    {
        return $this->repository(C::__('BadgeCategory'));
    }
}