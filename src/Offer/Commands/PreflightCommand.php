<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

class PreflightCommand extends AbstractCommand
{
    /**
     * @var Permission
     */
    private $permission;

    /**
     * @param string $itemId
     * @param Permission $permission
     */
    public function __construct($itemId, $permission)
    {
        parent::__construct($itemId);
        $this->permission = $permission;
    }

    /**
     * @return Permission
     */
    public function getPermission()
    {
        return $this->permission;
    }
}
