<?php

declare(strict_types=1);

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

    public function getPermission(): Permission
    {
        return $this->permission;
    }
}
