<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Entity;

use CMTV\Badges\Constants as C;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null badge_id
 * @property array user_criteria
 * @property int badge_category_id
 * @property string icon_type
 * @property string fa_icon
 * @property string image_url
 * @property string image_url_2x
 * @property string image_url_3x
 * @property string image_url_4x
 * @property string class
 * @property int display_order
 *
 * GETTERS
 * @property int awarded_number
 *
 * RELATIONS
 * @property BadgeCategory Category
 */
class Badge extends TitleDescEntity
{
    //
    // STRUCTURE
    //

    public static function getStructure(Structure $structure)
    {
        $structure->table = C::_table('badge');
        $structure->shortName = C::__('Badge');
        $structure->primaryKey = 'badge_id';

        $structure->columns = [
            'badge_id' => [
                'type' => self::UINT,
                'autoIncrement' => true,
                'nullable' => true
            ],

            'user_criteria' => [
                'type' => self::JSON_ARRAY,
                'default' => []
            ],

            'icon_type' => [
                'type' => self::STR,
                'allowedValues' => ['fa', 'image']
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

            'badge_category_id' => [
                'type' => self::UINT
            ],

            'display_order' => [
                'type' => self::UINT,
                'default' => 10
            ]
        ];

        $structure->getters = [
            'awarded_number' => true
        ];

        $structure->relations = [
            'Category' => [
                'type' => self::TO_ONE,
                'entity' => C::__('BadgeCategory'),
                'conditions' => 'badge_category_id'
            ]
        ];

        parent::addTitleDescStructureElements($structure);

        return $structure;
    }

    //
    // GETTERS
    //

    public function getAwardedNumber()
    {
        return $this->finder(C::__('UserBadge'))->where('badge_id', $this->badge_id)->total();
    }

    //
    // LIFE CYCLE
    //

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->db()->delete(C::_table('user_badge'), 'badge_id = ?', $this->badge_id);
        $this->db()->delete('xf_user_alert', "content_type = 'badge' AND action = 'award'");
    }

    //
    //
    //

    public static function getPrePhrase(): string
    {
        return C::_('badge');
    }
}