<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQueryInterface;
use ValueObjects\StringLiteral\StringLiteral;

class CombinedResourceOwnerQuery implements ResourceOwnerQueryInterface
{
    /**
     * @var ResourceOwnerQueryInterface[]
     */
    private $permissionQueries;

    /**
     * CombinedPermissionQuery constructor.
     * @param ResourceOwnerQueryInterface[] $permissionQueries
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
