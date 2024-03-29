<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class PermissionSwitchVoter implements PermissionVoter
{
    /**
     * @var PermissionVoter[]
     */
    private array $mapping;

    private ?PermissionVoter $defaultVoter;

    public function isAllowed(
        Permission $permission,
        string $itemId,
        string $userId
    ): bool {
        if (!isset($this->mapping[$permission->toString()])) {
            return isset($this->defaultVoter) && $this->defaultVoter->isAllowed($permission, $itemId, $userId);
        }

        return $this->mapping[$permission->toString()]->isAllowed($permission, $itemId, $userId);
    }

    public function withVoter(
        PermissionVoter $voter,
        Permission ...$permissions
    ): self {
        $c = clone $this;

        foreach ($permissions as $permission) {
            $c->mapping[$permission->toString()] = $voter;
        }

        return $c;
    }

    public function withDefaultVoter(PermissionVoter $voter): self
    {
        $c = clone $this;
        $c->defaultVoter = $voter;
        return $c;
    }
}
