<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Delegates voting to another voter based on which permission needs checking.
 */
final class PermissionSplitVoter implements PermissionVoterInterface
{
    /**
     * @var \CultuurNet\UDB3\Security\Permission\PermissionVoterInterface[]
     */
    private $mapping;

    public function isAllowed(
        Permission $permission,
        StringLiteral $offerId,
        StringLiteral $userId
    ) {
        if (!isset($this->mapping[(string)$permission])) {
            return false;
        }

        return $this->mapping[(string)$permission]->isAllowed($permission, $offerId, $userId);
    }

    public function withVoter(
        PermissionVoterInterface $voter,
        Permission ...$permissions
    ): self {
        $c = clone $this;

        foreach ($permissions as $permission) {
            $c->mapping[(string)$permission] = $voter;
        }

        return $c;
    }
}
