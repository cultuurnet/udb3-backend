<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final readonly class UpdateBookingAvailabilityByReservationLink
{
    public function __construct(
        public Uuid $eventId,
        public Url $reservationLink,
        public BookingAvailability $bookingAvailability
    ) {
    }
}
