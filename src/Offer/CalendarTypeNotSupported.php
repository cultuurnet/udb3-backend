<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\CalendarType;
use Exception;

final class CalendarTypeNotSupported extends Exception
{
    public static function forCalendarType(CalendarType $calendarType): self
    {
        return new self(
            'Updating booking availability on calendar type: "' . $calendarType->toString() . '" is not supported.'
            . ' Only single and multiple calendar types can be updated.'
        );
    }
}
