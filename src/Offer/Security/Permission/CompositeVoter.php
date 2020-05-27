<?php

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class CompositeVoter implements PermissionVoterInterface
{
    /**
     * @var PermissionVoterInterface[]
     */
    private $voters;

    /**
     * @param PermissionVoterInterface[] ...$voters
     */
    public function __construct(PermissionVoterInterface ...$voters)
    {
        $this->voters = $voters;
    }

    /**
     * @param Permission $permission
     * @param StringLiteral $offerId
     * @param StringLiteral $userId
     * @return bool
     */
    public function isAllowed(
        Permission $permission,
        StringLiteral $offerId,
        StringLiteral $userId
    ) {
        foreach ($this->voters as $voter) {
            if ($voter->isAllowed($permission, $offerId, $userId)) {
                return true;
            }
        }
        return false;
    }
}
