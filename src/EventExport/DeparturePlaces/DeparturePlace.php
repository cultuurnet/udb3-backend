<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\DeparturePlaces;

final class DeparturePlace
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $postalCode,
        public readonly string $addressLocality
    ) {
    }
}
