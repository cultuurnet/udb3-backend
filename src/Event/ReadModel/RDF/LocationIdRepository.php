<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;

interface LocationIdRepository
{
    public function save(string $resourceId, LocationId $locationId): void;
    public function get(string $resourceId): ?LocationId;
}
