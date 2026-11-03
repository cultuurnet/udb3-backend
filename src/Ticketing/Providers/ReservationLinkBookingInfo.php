<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Providers;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

/**
 * What a ticket sales provider knows about a reservation link: the identifier it uses in its availability updates,
 * the current availability and the waiting list, if there is one.
 */
final class ReservationLinkBookingInfo
{
    public function __construct(
        public readonly string $identifier,
        public readonly BookingAvailability $bookingAvailability,
        public readonly ?Url $waitingListUrl
    ) {
    }
}
