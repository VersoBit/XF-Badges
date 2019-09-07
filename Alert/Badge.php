<?php
/**
 * Badges xF2 addon by CMTV
 * Enjoy!
 */

namespace CMTV\Badges\Alert;

use XF\Alert\AbstractHandler;
use XF\Mvc\Entity\Entity;

class Badge extends AbstractHandler
{
    public function canViewContent(Entity $entity, &$error = null)
    {
        return true;
    }

    public function getOptOutActions()
    {
        return [
            'award'
        ];
    }

    public function getOptOutDisplayOrder()
    {
        return 30010;
    }
}