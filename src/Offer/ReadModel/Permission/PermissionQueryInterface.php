<?php

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use ValueObjects\StringLiteral\StringLiteral;

interface PermissionQueryInterface
{
    /**
     * @return StringLiteral[] A list of offer ids.
     */
    public function getEditableOffers(StringLiteral $uitId);
}
