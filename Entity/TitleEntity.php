<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * GETTERS
 * @property \XF\Phrase title
 *
 * RELATIONS
 * @property \XF\Entity\Phrase MasterTitle
 */
abstract class TitleEntity extends Entity
{
    protected static function addTitleStructureElements(Structure $structure)
    {
        if (!isset($structure->primaryKey))
        {
            throw new \LogicException(get_called_class() . '::addTitleStructureElements() must be called after setting "primaryKey" property!');
        }

        $structure->getters['title'] = true;

        $structure->relations['MasterTitle'] = [
            'entity' => 'XF:Phrase',
            'type' => self::TO_ONE,
            'conditions' => [
                ['language_id', '=', 0],
                ['title', '=', static::getPrePhrase() . '_title.', '$' . $structure->primaryKey]
            ]
        ];
    }

    //
    // LIFE CYCLE
    //

    protected function _postDelete()
    {
        if ($this->MasterTitle)
        {
            $this->MasterTitle->delete();
        }
    }

    //
    // GETTERS
    //

    public function getTitle()
    {
        return \XF::phrase(self::getTitlePhraseName());
    }

    //
    // UTIL
    //

    public function getMasterTitlePhrase()
    {
        $phrase = $this->MasterTitle;

        if (!$phrase)
        {
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function () { return $this->getTitlePhraseName(); }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = '';
        }

        return $phrase;
    }

    public function getTitlePhraseName()
    {
        return static::getPrePhrase() . '_title.' . $this->getEntityId();
    }

    //
    //
    //

    public static function getPrePhrase(): string
    {
        throw new \LogicException(get_called_class() . '::getPrePhrase() must be overriden!');
    }
}