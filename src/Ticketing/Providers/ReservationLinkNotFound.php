<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Providers;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Exception;

/**
 * The provider has no open event behind the link, for example because the venue hasn't opened it yet or closed it.
 */
final class ReservationLinkNotFound extends Exception
{
    public static function forLink(Url $reservationLink): self
    {
        return new self('No event found for reservation link ' . $reservationLink->toString());
    }
}
