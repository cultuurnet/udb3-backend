<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class AppConfigUserPermissionsReadRepository implements UserPermissionsReadRepositoryInterface
{

    /**
     * @var array<string, Permission[]>
     */
    private array $clientIdToPermissions;

    private UserPermissionsReadRepositoryInterface $userPermissionsReadRepository;


    public function __construct(UserPermissionsReadRepositoryInterface $userPermissionsReadRepository, array $clientIdToPermissionsConfig)
    {
        $this->userPermissionsReadRepository = $userPermissionsReadRepository;
        $this->clientIdToPermissions = $clientIdToPermissionsConfig;
    }


    public function getPermissions(string $userId): array
    {
        return $this->clientIdToPermissions[$userId] ??
            $this->userPermissionsReadRepository->getPermissions($userId);
    }

    public function hasPermission(string $userId, Permission $permission): bool
    {
        return in_array($permission, $this->getPermissions($userId));
    }
}