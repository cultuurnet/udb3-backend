<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class AnyOfVoter implements PermissionVoter
{
    /**
     * @var PermissionVoter[]
     */
    private $voters;

    /**
     * @param PermissionVoter[] ...$voters
     */
    public function __construct(PermissionVoter ...$voters)
    {
        $this->voters = $voters;
    }

    /**
     * @return bool
     */
    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ) {
        foreach ($this->voters as $voter) {
            if ($voter->isAllowed($permission, $itemId, $userId)) {
                return true;
            }
        }
        return false;
    }
}
