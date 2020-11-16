<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Pub\Controller;

use CMTV\Badges\Constants as C;
use XF;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Badge extends AbstractController
{
    public function actionAwardedList(ParameterBag $params)
    {
        $badge = $this->assertBadgeExists($params['badge_id']);

        $page = $this->filterPage();
        $perPage = 20;

        $userBadgesFinder = $this->finder(C::__('UserBadge'))
            ->where('badge_id', $params['badge_id'])
            ->limitByPage($page, $perPage);

        $breadcrumbs = [
            [
                'href' => $this->buildLink('help'),
                'value' => XF::phrase('help')
            ],

            [
                'href' => $this->buildLink('help/badges'),
                'value' => XF::phrase(C::_('badges'))
            ]
        ];

        $viewParams = [
            'badge' => $badge,
            'userBadges' => $userBadgesFinder->fetch(),

            'breadcrumbs' => $breadcrumbs,

            'page' => $page,
            'perPage' => $perPage,
            'total' => $userBadgesFinder->total()
        ];

        return $this->view(
            C::__('Badge\AwardedList'),
            C::_('awarded_list'),
            $viewParams
        );
    }

    protected function assertBadgeExists($id, $with = null, $phraseKey = null): \CMTV\Badges\Entity\Badge
    {
        return $this->assertRecordExists(C::__('Badge'), $id, $with, $phraseKey);
    }
}