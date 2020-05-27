<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface AuthorizableCommandInterface
{
    /**
     * @return string
     */
    public function getItemId();

    /**
     * @return Permission
     */
    public function getPermission();
}
