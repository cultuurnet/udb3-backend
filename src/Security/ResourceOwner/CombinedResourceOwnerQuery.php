<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

use ValueObjects\StringLiteral\StringLiteral;

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

    /**
     * @return StringLiteral[] A list of offer ids.
     */
    public function getEditableResourceIds(StringLiteral $userId)
    {
        $editableOffers = [];

        foreach ($this->permissionQueries as $permissionQuery) {
            $editableOffers = array_merge(
                $editableOffers,
                $permissionQuery->getEditableResourceIds($userId)
            );
        }

        return $editableOffers;
    }
}
