<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithDateRange;
use DateTimeImmutable;

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
        return DateTimeFactory::fromAtom('2100-01-01T00:00:00+00:00');
    }
}
