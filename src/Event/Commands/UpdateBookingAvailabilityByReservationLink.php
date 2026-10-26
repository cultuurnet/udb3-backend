<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class UpdateBookingAvailabilityByReservationLink
{
    public function __construct(
        public readonly string $eventId,
        public readonly Url $reservationLink,
        public readonly BookingAvailability $bookingAvailability
    ) {
    }
}
