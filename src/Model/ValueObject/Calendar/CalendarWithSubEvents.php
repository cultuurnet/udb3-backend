<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

interface CalendarWithSubEvents
{
    public function getSubEvents(): SubEvents;

    public function withStatusOnSubEvents(Status $status): CalendarWithSubEvents;

    public function withBookingAvailabilityOnSubEvents(BookingAvailability $bookingAvailability): CalendarWithSubEvents;
}
