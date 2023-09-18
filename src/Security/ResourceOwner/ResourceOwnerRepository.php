<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

interface ResourceOwnerRepository
{
    public function markResourceEditableByUser(
        string $resourceId,
        string $userId
    ): void;

    public function markResourceEditableByNewUser(
        string $resourceId,
        string $userId
    ): void;
}
