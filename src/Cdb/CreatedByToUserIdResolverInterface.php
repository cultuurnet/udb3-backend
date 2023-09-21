<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

interface CreatedByToUserIdResolverInterface
{
    public function resolveCreatedByToUserId(string $createdByIdentifier): ?string;
}
