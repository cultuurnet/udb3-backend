<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Providers;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Exception;

/**
 * The link is not a reservation link of the provider, for example a link of its test environment in production.
 * Trying again won't help.
 */
final class InvalidReservationLink extends Exception
{
    public static function forLink(Url $reservationLink): self
    {
        return new self('Not a valid reservation link: ' . $reservationLink->toString());
    }
}
