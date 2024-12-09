<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;

class OfferUpdate
{
    public static function calendar(Calendar $calendar): \Closure
    {
        return function ($body) use ($calendar) {
            // Purge any existing calendar data
            unset(
                $body->calendarType,
                $body->startDate,
                $body->endDate,
                $body->subEvent,
                $body->openingHours
            );

            return (object) array_merge(
                (array) $body,
                (new CalendarNormalizer())->normalize($calendar)
            );
        };
    }
}
