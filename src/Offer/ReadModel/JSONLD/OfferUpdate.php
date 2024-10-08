<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Calendar\Calendar;

/**
 * Class OfferUpdate
 *
 * Creates callbacks that can be applied to json-ld offers.
 *
 * @package CultuurNet\UDB3\Offer\ReadModel\JSONLD
 *
 */
class OfferUpdate
{
    /**
     * @param Calendar $calendar
     *  The calendar to use when updating the offer
     */
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
                $calendar->toJsonLd()
            );
        };
    }
}
