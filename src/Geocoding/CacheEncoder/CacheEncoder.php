<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\CacheEncoder;

use Geocoder\Location;

interface CacheEncoder
{
    public function encode(Location $location): array;

    public function getKey(string $address): string;
}
