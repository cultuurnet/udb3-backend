<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\StringLiteral;

final class AppConfigUserConstraintsReadRepository implements UserConstraintsReadRepositoryInterface
{
    /**
     * @var array<string, array>
     */
    private array $clientIdToPermissionsConfig;

    private UserConstraintsReadRepositoryInterface $userConstraintsReadRepository;


    public function __construct(UserConstraintsReadRepositoryInterface $userConstraintsReadRepository, array $clientIdToPermissionsConfig)
    {
        $this->userConstraintsReadRepository = $userConstraintsReadRepository;
        $this->clientIdToPermissionsConfig = $clientIdToPermissionsConfig;
    }

    public function getByUserAndPermission(StringLiteral $userId, Permission $permission): array
    {
        $config = $this->clientIdToPermissionsConfig[$userId->toNative()] ?? [];
        $permissions = $config['permissions'] ?? [];
        $constraint = $config['sapi3_constraint'] ?? null;

        if (!in_array($permission, $permissions) || $constraint === null) {
            return $this->userConstraintsReadRepository->getByUserAndPermission($userId, $permission);
        }

        return [new StringLiteral($constraint)];
    }
}
