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
 * @property \XF\Phrase description
 *
 * RELATIONS
 * @property \XF\Entity\Phrase MasterDescription
 */
abstract class TitleDescEntity extends TitleEntity
{
    protected static function addTitleDescStructureElements(Structure $structure)
    {
        parent::addTitleStructureElements($structure);

        $structure->getters['description'] = true;

        $structure->relations['MasterDescription'] = [
            'entity' => 'XF:Phrase',
            'type' => Entity::TO_ONE,
            'conditions' => [
                ['language_id', '=', 0],
                ['title', '=', static::getPrePhrase() . '_description.', '$' . $structure->primaryKey]
            ]
        ];
    }

    //
    // LIFE CYCLE
    //

    protected function _postDelete()
    {
        parent::_postDelete();

        if ($this->MasterDescription)
        {
            $this->MasterDescription->delete();
        }
    }

    //
    // GETTERS
    //

    public function getDescription()
    {
        return \XF::phrase(self::getDescriptionPhraseName());
    }

    //
    // UTIL
    //

    public function getMasterDescriptionPhrase()
    {
        $phrase = $this->MasterDescription;

        if (!$phrase)
        {
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function () { return $this->getDescriptionPhraseName(); }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = '';
        }

        return $phrase;
    }

    public function getDescriptionPhraseName()
    {
        return static::getPrePhrase() . '_description.' . $this->getEntityId();
    }
}