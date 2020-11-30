<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Entity;

use CMTV\Badges\Constants as C;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null badge_category_id
 * @property string icon_type
 * @property string fa_icon
 * @property string image_url
 * @property string image_url_2x
 * @property string image_url_3x
 * @property string image_url_4x
 * @property string class
 * @property int display_order
 *
 * RELATIONS
 * @property AbstractCollection|Badge[] Badges
 */
class BadgeCategory extends TitleEntity
{
    //
    // STRUCTURE
    //

    public static function getStructure(Structure $structure)
    {
        $structure->table = C::_table('badge_category');
        $structure->shortName = C::__('BadgeCategory');
        $structure->primaryKey = 'badge_category_id';

        $structure->columns = [
            'badge_category_id' => [
                'type' => self::UINT,
                'autoIncrement' => true,
                'nullable' => true
            ],

            'icon_type' => [
                'type' => self::STR,
                'default' => '',
                'allowedValues' => ['', 'fa', 'image']
            ],

            'fa_icon' => [
                'type' => self::STR,
                'maxLength' => 256,
                'default' => ''
            ],

            'image_url' => [
                'type' => self::STR,
                'default' => '',
                'maxLength' => 512
            ],

            'image_url_2x' => [
                'type' => self::STR,
                'default' => '',
                'maxLength' => 512
            ],

            'image_url_3x' => [
                'type' => self::STR,
                'default' => '',
                'maxLength' => 512
            ],

            'image_url_4x' => [
                'type' => self::STR,
                'default' => '',
                'maxLength' => 512
            ],

            'class' => [
                'type' => self::STR,
                'maxLength' => 256,
                'default' => ''
            ],

            'display_order' => [
                'type' => self::UINT,
                'default' => 10
            ]
        ];

        $structure->relations = [
            'Badges' => [
                'entity' => C::__('Badge'),
                'type' => self::TO_MANY,
                'conditions' => [
                    ['badge_category_id', '=', '$badge_category_id']
                ]
            ]
        ];

        parent::addTitleStructureElements($structure);

        return $structure;
    }

    //
    // LIFE CYCLE
    //

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->db()->update(
            C::_table('badge'),
            ['badge_category_id' => 0],
            'badge_category_id = ?',
            $this->badge_category_id
        );
    }

    //
    //
    //

    public static function getPrePhrase(): string
    {
        return C::_('badge_category');
    }
}