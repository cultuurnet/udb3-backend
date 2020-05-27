<?php

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Offer\Security\UserPermissionMatcherInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class RoleConstraintVoter implements PermissionVoterInterface
{
    /**
     * @var UserPermissionMatcherInterface
     */
    private $userPermissionMatcher;

    /**
     * @param UserPermissionMatcherInterface $userPermissionMatcher
     */
    public function __construct(UserPermissionMatcherInterface $userPermissionMatcher)
    {
        $this->userPermissionMatcher = $userPermissionMatcher;
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
        return $this->userPermissionMatcher->itMatchesOffer(
            $userId,
            $permission,
            $offerId
        );
    }
}
