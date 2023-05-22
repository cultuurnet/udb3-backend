<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\ExternalId;

class ArrayMappingService implements MappingServiceInterface
{
    private array $externalIdMapping;

    public function __construct(array $externalIdMapping)
    {
        $this->externalIdMapping = $externalIdMapping;
    }

    public function getCdbId(string $externalId): ?string
    {
        if (isset($this->externalIdMapping[$externalId])) {
            return (string) $this->externalIdMapping[$externalId];
        } else {
            return null;
        }
    }
}
