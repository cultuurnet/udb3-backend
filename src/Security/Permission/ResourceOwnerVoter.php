<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class ResourceOwnerVoter implements PermissionVoter
{
    /**
     * @var PermissionQueryInterface
     */
    private $permissionRepository;


    public function __construct(PermissionQueryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        $editableEvents = $this->permissionRepository->getEditableOffers($userId);
        return in_array($itemId, $editableEvents);
    }
}
