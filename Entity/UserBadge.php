<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Entity;

use CMTV\Badges\Constants as C;
use CMTV\Badges\XF\Entity\User;
use XF;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int user_id
 * @property int badge_id
 * @property int award_date
 * @property string reason
 * @property bool featured
 *
 * RELATIONS
 * @property User User
 * @property Badge Badge
 */
class UserBadge extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = C::_table('user_badge');
        $structure->shortName = C::__('UserBadge');
        $structure->primaryKey = ['user_id', 'badge_id'];

        $structure->columns = [
            'user_id' => [
                'type' => self::UINT,
                'required' => true
            ],

            'badge_id' => [
                'type' => self::UINT,
                'required' => true
            ],

            'award_date' => [
                'type' => self::UINT,
                'default' => XF::$time
            ],

            'reason' => [
                'type' => self::STR,
                'default' => ''
            ],

            'featured' => [
                'type' => self::BOOL,
                'default' => false
            ]
        ];

        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],

            'Badge' => [
                'entity' => C::__('Badge'),
                'type' => self::TO_ONE,
                'conditions' => 'badge_id',
                'primary' => true
            ]
        ];

        return $structure;
    }
}