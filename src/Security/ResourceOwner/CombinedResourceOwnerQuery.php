<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

final class CombinedResourceOwnerQuery implements ResourceOwnerQuery
{
    /**
     * @var ResourceOwnerQuery[]
     */
    private array $permissionQueries;

    /**
     * @param ResourceOwnerQuery[] $permissionQueries
     */
    public function __construct(array $permissionQueries)
    {
        $this->permissionQueries = $permissionQueries;
    }

    public function getEditableResourceIds(string $userId): array
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
