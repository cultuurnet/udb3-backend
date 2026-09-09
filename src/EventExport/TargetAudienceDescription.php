<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use stdClass;

final class TargetAudienceDescription
{
    private const CHILDREN_ONLY = 'voor kinderen alleen';

    private const CHILDREN_WITH_GUARDIAN = 'voor kinderen samen met hun familie of een andere begeleider';

    /**
     * A child is younger than this, so an age range starting at it is no longer aimed at children.
     */
    private const CHILD_AGE_LIMIT = 12;

    /**
     * An event that is only for children says so itself. One that welcomes children along with
     * whoever brings them does not, so it is recognised by an age range that reaches below the age
     * a child stops being one. An event for no particular age says nothing about its audience, and
     * answers null so that every export can leave it out in its own way.
     */
    public static function fromEvent(stdClass $event): ?string
    {
        // The projector only ever writes childrenOnly: true and unsets it otherwise, so a false can
        // only come from an older projection and falls through to the age range rather than being
        // read as a deliberate no.
        if (isset($event->childrenOnly) && $event->childrenOnly === true) {
            return self::CHILDREN_ONLY;
        }

        return self::isAimedAtChildren($event) ? self::CHILDREN_WITH_GUARDIAN : null;
    }

    private static function isAimedAtChildren(stdClass $event): bool
    {
        $ageRange = AgeRangeFactory::specificFromString($event->typicalAgeRange ?? null);

        if ($ageRange === null) {
            return false;
        }

        // An age range without a start covers everyone from birth onwards.
        $from = $ageRange->getFrom()?->toInteger() ?? 0;

        return $from < self::CHILD_AGE_LIMIT;
    }
}
