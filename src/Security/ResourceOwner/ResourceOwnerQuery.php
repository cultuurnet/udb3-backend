<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

interface ResourceOwnerQuery
{
    /**
     * @return string[] A list of resource ids that the given user can edit.
     */
    public function getEditableResourceIds(string $userId): array;
}
