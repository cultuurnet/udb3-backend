<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithDateRange;

class AvailableTo
{
    /**
     * @return \DateTimeImmutable
     */
    public static function createFromCalendar(Calendar $calendar)
    {
        if ($calendar instanceof CalendarWithDateRange) {
            return $calendar->getEndDate();
        } else {
            return self::forever();
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    public static function forever()
    {
        return \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            '2100-01-01T00:00:00+00:00'
        );
    }
}
