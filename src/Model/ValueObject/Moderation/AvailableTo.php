<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithDateRange;
use DateTimeImmutable;
use DateTimeInterface;

class AvailableTo
{
    public static function createFromCalendar(Calendar $calendar): DateTimeImmutable
    {
        if ($calendar instanceof CalendarWithDateRange) {
            return $calendar->getEndDate();
        }

        return self::forever();
    }

    public static function forever(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(
            DateTimeInterface::ATOM,
            '2100-01-01T00:00:00+00:00'
        );
    }
}
