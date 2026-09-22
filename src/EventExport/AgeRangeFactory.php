<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\InvalidAgeRangeException;

final class AgeRangeFactory
{
    /**
     * Reads a typicalAgeRange straight off a projection, where it is whatever was stored rather
     * than a value object. Everything that does not describe a specific age is treated as absent:
     * a value that is not a range at all, and an all ages event, which says nothing about who the
     * event is for and is what the projection fills in when nothing was entered.
     */
    public static function specificFromString(mixed $typicalAgeRange): ?AgeRange
    {
        if (!is_string($typicalAgeRange)) {
            return null;
        }

        try {
            $ageRange = AgeRange::fromString($typicalAgeRange);
        } catch (InvalidAgeRangeException) {
            return null;
        }

        // Note that AgeRange::toString() also answers "-" for "0-", which is why isForAllAges()
        // is asked instead of comparing the original string.
        return $ageRange->isForAllAges() ? null : $ageRange;
    }

    public static function hasSpecificAgeRange(mixed $typicalAgeRange): bool
    {
        return self::specificFromString($typicalAgeRange) !== null;
    }
}
