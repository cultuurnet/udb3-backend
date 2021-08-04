<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\CalendarType;
use Exception;

final class UpdateBookingAvailabilityNotAllowed extends Exception
{
    public static function forCalendarType(CalendarType $calendarType): self
    {
        return new self(
            'Not allowed to update booking availability on calendar type: "' . $calendarType->getName() . '".'
            . ' Only single and multiple calendar types can be updated.'
        );
    }
}
