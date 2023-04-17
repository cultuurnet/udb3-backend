<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;

final class InMemoryLocationIdRepository implements LocationIdRepository
{
    private array $locations;

    public function save(string $resourceId, LocationId $locationId): void
    {
        $this->locations[$resourceId] = $locationId->toString();
    }

    public function get(string $resourceId): ?LocationId
    {
        return $this->locations[$resourceId] ? new LocationId($this->locations[$resourceId]) : null;
    }
}
