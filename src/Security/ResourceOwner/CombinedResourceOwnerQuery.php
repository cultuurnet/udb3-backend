<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

use CultuurNet\UDB3\StringLiteral;

class CombinedResourceOwnerQuery implements ResourceOwnerQuery
{
    /**
     * @var ResourceOwnerQuery[]
     */
    private $permissionQueries;

    /**
     * CombinedPermissionQuery constructor.
     * @param ResourceOwnerQuery[] $permissionQueries
     */
    public function __construct(array $permissionQueries)
    {
        $this->permissionQueries = $permissionQueries;
    }

    public function getEditableResourceIds(StringLiteral $userId): array
    {
        $editableResourceIds = [];

        foreach ($this->permissionQueries as $permissionQuery) {
            $editableResourceIds = array_merge(
                $editableResourceIds,
                $permissionQuery->getEditableResourceIds($userId)
            );
        }

        return $editableResourceIds;
    }
}
