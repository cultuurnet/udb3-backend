<?php

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class OwnerVoter implements PermissionVoterInterface
{
    /**
     * @var PermissionQueryInterface
     */
    private $permissionRepository;

    /**
     * @param PermissionQueryInterface $permissionRepository
     */
    public function __construct(PermissionQueryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
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
        $editableEvents = $this->permissionRepository->getEditableOffers($userId);
        return in_array($offerId, $editableEvents);
    }
}
