<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\StringLiteral;

class ResourceOwnerVoter implements PermissionVoter
{
    /**
     * @var ResourceOwnerQuery
     */
    private $permissionRepository;


    public function __construct(ResourceOwnerQuery $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        $editableEvents = $this->permissionRepository->getEditableResourceIds($userId);
        return in_array($itemId, $editableEvents);
    }
}
