<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Event\EventTypeResolver;
use stdClass;

final class OvernightStay
{
    /**
     * An occurrence only carries "hasOvernightStay" when it has one, so its absence cannot be told
     * apart from an event type that could never have one. Only camps and vacations can, which is
     * why any other event type returns null instead of false.
     */
    public static function forEvent(stdClass $event): ?bool
    {
        if (!EventTypeResolver::isOvernightStayAllowed(self::getEventTypeId($event))) {
            return null;
        }

        if (!isset($event->subEvent) || !is_array($event->subEvent)) {
            return false;
        }

        foreach ($event->subEvent as $subEvent) {
            if (isset($subEvent->hasOvernightStay) && $subEvent->hasOvernightStay === true) {
                return true;
            }
        }

        return false;
    }

    private static function getEventTypeId(stdClass $event): ?string
    {
        if (!isset($event->terms) || !is_array($event->terms)) {
            return null;
        }

        foreach ($event->terms as $term) {
            if (isset($term->domain, $term->id) && $term->domain === 'eventtype') {
                return $term->id;
            }
        }

        return null;
    }
}
