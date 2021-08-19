<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

interface Calendar
{
    public function getType(): CalendarType;

    public function getStatus(): Status;

    public function withStatus(Status $status): Calendar;

    public function getBookingAvailability(): BookingAvailabilityType;

    public function withBookingAvailability(BookingAvailabilityType $bookingAvailability): Calendar;
}
