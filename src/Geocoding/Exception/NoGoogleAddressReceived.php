<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\Exception;

use RuntimeException;

class NoGoogleAddressReceived extends RuntimeException
{
    public const ERROR = 'Did not receive a google address to enrich';

    public function __construct()
    {
        parent::__construct(self::ERROR);
    }
}
