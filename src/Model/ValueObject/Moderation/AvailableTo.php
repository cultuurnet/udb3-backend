<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithDateRange;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use DateTimeImmutable;
use DateTimeInterface;

class AvailableTo
{
    public static function createFromCalendar(Calendar $calendar, ?Category $eventType): DateTimeImmutable
    {
        if (!$calendar instanceof CalendarWithDateRange) {
            return self::forever();
        }

        /** @var DateTimeInterface $availableTo */
        $availableTo = $calendar->getEndDate();

        if ($eventType && EventTypeResolver::isOnlyAvailableUntilStartDate($eventType)) {
            /** @var DateTimeInterface $availableTo */
            $availableTo = $calendar->getStartDate();
        }

        /**
         * https://jira.uitdatabank.be/browse/III-1581
         * When available to has no time information, it needs to be set to almost midnight 23:59:59.
         */
        if ($availableTo->format('H:i:s') === '00:00:00') {
            $availableTo = DateTimeFactory::fromAtom($availableTo->format(DATE_ATOM))
                ->setTime(23, 59, 59);
        }

        return new DateTimeImmutable($availableTo->format(DATE_ATOM), $availableTo->getTimezone());
    }

    public static function forever(): DateTimeImmutable
    {
        return DateTimeFactory::fromAtom('2100-01-01T00:00:00+00:00');
    }
}
