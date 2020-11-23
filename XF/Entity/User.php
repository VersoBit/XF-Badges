<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\XF\Entity;

use CMTV\Badges\Constants as C;
use CMTV\Badges\Entity\Badge;
use CMTV\Badges\Repository\UserBadge;
use XF;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int cmtv_badges_badge_count
 *
 * GETTERS
 * @property int badge_count
 * @property AbstractCollection|Badge[] featured_badges
 * @property AbstractCollection|Badge[] recent_badges
 *
 * RELATIONS
 * @property AbstractCollection|Badge[] Badges
 */
class User extends XFCP_User
{
    //
    // STRUCTURE
    //

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $columns = [
            'cmtv_badges_badge_count' => [
                'type' => self::UINT,
                'default' => 0,
                'changeLog' => false
            ]
        ];

        $structure->columns = array_merge($columns, $structure->columns);

        $getters = [
            'badge_count' => true,
            'featured_badges' => true,
            'recent_badges' => true
        ];

        $structure->getters = array_merge($getters, $structure->getters);

        $relations = [
            'Badges' => [
                'entity' => C::__('UserBadge'),
                'type' => self::TO_MANY,
                'conditions' => [
                    ['user_id', '=', '$user_id']
                ]
            ]
        ];

        $structure->relations = array_merge($relations, $structure->relations);

        return $structure;
    }

    //
    // GETTERS
    //

    public function getBadgeCount()
    {
        return $this->finder(C::__('UserBadge'))->where('user_id', $this->user_id)->total();
    }

    public function getFeaturedBadges()
    {
        return $this->getUserBadgeRepo()->getFeaturedUserBadges($this);
    }

    public function getRecentBadges()
    {
        return $this->getUserBadgeRepo()->getRecentUserBadges($this);
    }

    //
    // LIFECYCLE
    //

    protected function _postDelete()
    {
        $db = $this->db();
        $userId = $this->user_id;

        $db->delete(C::_table('user_badge'), 'user_id = ?', $userId);

        parent::_postDelete();
    }

    //
    // PERMISSIONS
    //

    public function canManageFeaturedBadges()
    {
        if (!$this->user_id) {
            return false;
        }

        if ($this->user_id != XF::visitor()->user_id) {
            return false;
        }

        if (!$this->hasPermission(C::_(), 'manageFeatured')) {
            return false;
        }

        return ($this->hasPermission(C::_(), 'featuredNumber') != 0);
    }

    public function canAddFeaturedBadge()
    {
        if (!$this->canManageFeaturedBadges()) {
            return false;
        }

        $featuredAllowed = $this->hasPermission(C::_(), 'featuredNumber');

        if ($featuredAllowed == -1) {
            return true;
        }

        $actualFeatured = $this->getUserBadgeRepo()->getUserBadgeCount($this->user_id, true);

        return (($featuredAllowed == -1) ?: ($actualFeatured < $featuredAllowed));
    }

    public function canAwardWithBadge()
    {
        $visitor = XF::visitor();

        if (!$this->user_id || !$visitor->is_moderator) {
            return false;
        }

        $totalBadges = $this->finder(C::__('Badge'))->total();

        if ($totalBadges == 0 || $this->badge_count == $totalBadges) {
            return false;
        }

        return $visitor->hasPermission(C::_(), 'award');
    }

    public function canEditBadgeReason()
    {
        $visitor = XF::visitor();

        if (!$this->user_id || !$visitor->is_moderator) {
            return false;
        }

        return $visitor->hasPermission(C::_(), 'award');
    }

    public function canTakeAwayBadge()
    {
        $visitor = XF::visitor();

        if (!$this->user_id || !$visitor->is_moderator || !$this->badge_count) {
            return false;
        }

        return $visitor->hasPermission(C::_(), 'takeAway');
    }

    //
    // UTIL
    //

    public function getUserBadgeRepo(): UserBadge
    {
        return $this->repository(C::__('UserBadge'));
    }
}