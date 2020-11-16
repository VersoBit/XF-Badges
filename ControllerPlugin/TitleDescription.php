<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\ControllerPlugin;

use CMTV\Badges\Entity\TitleDescEntity;
use CMTV\Badges\Entity\TitleEntity;
use XF;
use XF\ControllerPlugin\AbstractPlugin;
use XF\Mvc\FormAction;

class TitleDescription extends AbstractPlugin
{
    public function saveTitle(FormAction $form, TitleEntity $entity)
    {
        $titlePhrase = $this->filter('title', 'str');

        $form->validate(function (FormAction $form) use ($titlePhrase) {
            if ($titlePhrase === '') {
                $form->logError(XF::phrase('please_enter_valid_title'), 'title');
            }
        });

        $form->apply(function () use ($titlePhrase, $entity) {
            $masterTitle = $entity->getMasterTitlePhrase();
            $masterTitle->phrase_text = $titlePhrase;
            $masterTitle->save();
        });

        return $form;
    }

    public function saveTitleDescription(FormAction $form, TitleDescEntity $entity)
    {
        $this->saveTitle($form, $entity);

        $descPhrase = $this->filter('description', 'str');

        $form->apply(function () use ($descPhrase, $entity) {
            $masterDescription = $entity->getMasterDescriptionPhrase();
            $masterDescription->phrase_text = $descPhrase;
            $masterDescription->save();
        });

        return $form;
    }
}