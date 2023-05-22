<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\ExternalId;

interface MappingServiceInterface
{
    public function getCdbId(string $externalId): ?string;
}
