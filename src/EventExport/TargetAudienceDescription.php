<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\DateTimeInvalid;
use DateTimeImmutable;
use stdClass;

final class TargetAudienceDescription
{
    private const CHILDREN_ONLY = 'voor kinderen alleen';

    private const CHILDREN_WITH_GUARDIAN = 'voor kinderen samen met hun familie of een andere begeleider';

    private const CHILD_AGE_LIMIT = 12;

    public static function fromEvent(stdClass $event): ?string
    {
        if (isset($event->childrenOnly) && $event->childrenOnly === true) {
            return self::CHILDREN_ONLY;
        }

        return self::isAimedAtChildren($event) ? self::CHILDREN_WITH_GUARDIAN : null;
    }

    private static function isAimedAtChildren(stdClass $event): bool
    {
        $ageRange = AgeRangeFactory::specificFromString($event->typicalAgeRange ?? null);

        if ($ageRange === null) {
            return self::isAimedAtChildrenByBirthdate($event);
        }

        $from = $ageRange->getFrom()?->toInteger() ?? 0;

        return $from < self::CHILD_AGE_LIMIT;
    }

    private static function isAimedAtChildrenByBirthdate(stdClass $event): bool
    {
        $birthdateRange = BirthdateRangeFactory::fromJson($event->birthdateRange ?? null);
        $reference = self::referenceDate($event);

        if ($birthdateRange === null || $reference === null) {
            return false;
        }

        // Whoever was born last is the youngest of the audience, so the end of the range carries
        // its lowest age.
        $youngest = $birthdateRange->getTo();

        // An audience that is not born yet on the day counted towards is younger than any limit.
        if ($youngest > $reference) {
            return true;
        }

        return $youngest->diff($reference)->y < self::CHILD_AGE_LIMIT;
    }

    /**
     * The day an age is counted towards is the day the event starts. A permanent event never
     * starts, so there the day it became available is the only one on offer.
     */
    private static function referenceDate(stdClass $event): ?DateTimeImmutable
    {
        foreach ([$event->startDate ?? null, $event->availableFrom ?? null] as $date) {
            if (!is_string($date)) {
                continue;
            }

            try {
                return DateTimeFactory::fromISO8601($date);
            } catch (DateTimeInvalid) {
                // An unreadable start date is no better than an absent one, so the next candidate
                // still gets its turn.
                continue;
            }
        }

        return null;
    }
}
