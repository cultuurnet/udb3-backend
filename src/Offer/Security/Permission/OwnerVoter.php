<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use ValueObjects\StringLiteral\StringLiteral;

class OwnerVoter implements PermissionVoter
{
    /**
     * @var PermissionQueryInterface
     */
    private $permissionRepository;


    public function __construct(PermissionQueryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * @return bool
     */
    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ) {
        $editableEvents = $this->permissionRepository->getEditableOffers($userId);
        return in_array($itemId, $editableEvents);
    }
}
