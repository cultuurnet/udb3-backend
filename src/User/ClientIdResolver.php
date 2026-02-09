<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

interface ClientIdResolver
{
    public function hasEntryAccess(string $clientId): bool;
}
