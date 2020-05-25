<?php

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use ValueObjects\StringLiteral\StringLiteral;

class CombinedPermissionQuery implements PermissionQueryInterface
{
    /**
     * @var PermissionQueryInterface[]
     */
    private $permissionQueries;

    /**
     * CombinedPermissionQuery constructor.
     * @param PermissionQueryInterface[] $permissionQueries
     */
    public function __construct(array $permissionQueries)
    {
        $this->permissionQueries = $permissionQueries;
    }

    /**
     * @param StringLiteral $uitId
     * @return StringLiteral[] A list of offer ids.
     */
    public function getEditableOffers(StringLiteral $uitId)
    {
        $editableOffers = [];

        foreach ($this->permissionQueries as $permissionQuery) {
            $editableOffers = array_merge(
                $editableOffers,
                $permissionQuery->getEditableOffers($uitId)
            );
        }

        return $editableOffers;
    }
}
