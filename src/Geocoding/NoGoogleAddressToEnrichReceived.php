<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use RuntimeException;

class NoGoogleAddressToEnrichReceived extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Did not receive a google address to enrich');
    }
}
