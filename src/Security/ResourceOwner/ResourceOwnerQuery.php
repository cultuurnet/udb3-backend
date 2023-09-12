<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

use CultuurNet\UDB3\StringLiteral;

interface ResourceOwnerQuery
{
    /**
     * @return StringLiteral[] A list of resource ids that the given user can edit.
     */
    public function getEditableResourceIds(string $userId): array;
}
