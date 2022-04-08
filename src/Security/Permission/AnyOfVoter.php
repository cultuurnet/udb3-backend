<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\StringLiteral;

class AnyOfVoter implements PermissionVoter
{
    /**
     * @var PermissionVoter[]
     */
    private $voters;

    private ?bool $isAllowed = null;

    /**
     * @param PermissionVoter[] ...$voters
     */
    public function __construct(PermissionVoter ...$voters)
    {
        $this->voters = $voters;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        if ($this->isAllowed !== null) {
            return $this->isAllowed;
        }

        foreach ($this->voters as $voter) {
            if ($voter->isAllowed($permission, $itemId, $userId)) {
                $this->isAllowed = true;
                return true;
            }
        }

        $this->isAllowed = false;
        return false;
    }
}
