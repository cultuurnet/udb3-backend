<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use ValueObjects\StringLiteral\StringLiteral;

interface PermissionQueryInterface
{
    /**
     * @return StringLiteral[] A list of resource ids that the given user can edit.
     */
    public function getEditableResourceIds(StringLiteral $uitId);
}
