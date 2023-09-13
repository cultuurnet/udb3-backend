<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class GodUserVoter implements PermissionVoter
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
        string $itemId,
        string $userId
    ): bool {
        return in_array($userId, $this->godUserIds);
    }
}
