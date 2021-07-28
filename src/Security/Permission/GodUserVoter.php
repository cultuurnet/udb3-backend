<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class GodUserVoter implements PermissionVoter
{
    /**
     * @var array
     */
    private $godUserIds;

    /**
     * @param string[] $godUserIds
     */
    public function __construct(array $godUserIds)
    {
        $this->godUserIds = $godUserIds;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        return in_array($userId->toNative(), $this->godUserIds);
    }
}
