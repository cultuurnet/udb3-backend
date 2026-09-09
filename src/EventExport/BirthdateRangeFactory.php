<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\InvalidAgeRangeException;
use DateTimeImmutable;
use stdClass;

final class BirthdateRangeFactory
{
    private const DISPLAY_FORMAT = 'd/m/Y';

    private const INPUT_FORMAT = 'Y-m-d';

    /**
     * Reads a birthdateRange straight off a projection, where it is whatever was stored rather than
     * a value object, so anything that does not describe a real range answers null.
     */
    public static function fromJson(mixed $birthdateRange): ?BirthdateRange
    {
        if (!$birthdateRange instanceof stdClass) {
            return null;
        }

        $from = self::parseDate($birthdateRange->from ?? null);
        $to = self::parseDate($birthdateRange->to ?? null);

        if ($from === null || $to === null) {
            return null;
        }

        try {
            return new BirthdateRange($from, $to);
        } catch (InvalidAgeRangeException) {
            return null;
        }
    }

    /**
     * The whole range as one value, for a column that holds it next to an age range like "6-12".
     */
    public static function formatRange(BirthdateRange $range): string
    {
        return self::formatDate($range->getFrom()) . ' - ' . self::formatDate($range->getTo());
    }

    /**
     * One end of a range, for a sentence that puts the two dates in different places.
     */
    public static function formatDate(DateTimeImmutable $date): string
    {
        return $date->format(self::DISPLAY_FORMAT);
    }

    private static function parseDate(mixed $date): ?DateTimeImmutable
    {
        if (!is_string($date)) {
            return null;
        }

        // The "!" resets the time part, so a range of a single day has an identical from and to.
        $parsed = DateTimeImmutable::createFromFormat('!' . self::INPUT_FORMAT, $date);

        // createFromFormat() silently rolls over out of range dates like 2026-13-45, so compare
        // the result against the input to only accept real dates.
        if ($parsed === false || $parsed->format(self::INPUT_FORMAT) !== $date) {
            return null;
        }

        return $parsed;
    }
}
