<?php

namespace CultuurNet\UDB3\Http\Event\Location;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use RuntimeException;

class LocationNotFound extends RuntimeException
{
    public static function withLocationId(LocationId $locationId): self
    {
        return new self('Location with id ' . $locationId->toNative() . ' could not be found');
    }
}
