<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

use ValueObjects\StringLiteral\StringLiteral;

interface ResourceOwnerQuery
{
    /**
     * @return StringLiteral[] A list of resource ids that the given user can edit.
     */
    public function getEditableResourceIds(StringLiteral $userId): array;
}
