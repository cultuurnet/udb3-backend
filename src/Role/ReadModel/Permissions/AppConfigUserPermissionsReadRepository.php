<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class AppConfigUserPermissionsReadRepository implements UserPermissionsReadRepositoryInterface
{

    /**
     * @var array<string, Permission[]>
     */
    private array $clientIdToPermissionsConfig;


    public function __construct(array $clientIdToPermissionsConfig)
    {
        $this->clientIdToPermissionsConfig = $clientIdToPermissionsConfig;
    }


    public function getPermissions(string $userId): array
    {
        return $this->clientIdToPermissionsConfig[$userId] ?? [];
    }

    public function hasPermission(string $userId, Permission $permission): bool
    {
        return in_array($permission, $this->getPermissions($userId), false);
    }
}