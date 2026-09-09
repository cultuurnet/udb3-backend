<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

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
            return false;
        }

        $from = $ageRange->getFrom()?->toInteger() ?? 0;

        return $from < self::CHILD_AGE_LIMIT;
    }
}
