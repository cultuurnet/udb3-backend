<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\InvalidAgeRangeException;
use DateTimeImmutable;
use stdClass;

final class BirthdateRangeDescription
{
    private const INPUT_FORMAT = 'Y-m-d';

    private const OUTPUT_FORMAT = 'd/m/Y';

    public static function fromBirthdateRange(stdClass $birthdateRange): ?string
    {
        $from = self::parseDate($birthdateRange->from ?? null);
        $to = self::parseDate($birthdateRange->to ?? null);

        if ($from === null || $to === null) {
            return null;
        }

        try {
            $range = new BirthdateRange($from, $to);
        } catch (InvalidAgeRangeException) {
            return null;
        }

        if ($range->getFrom()->format(self::INPUT_FORMAT) === $range->getTo()->format(self::INPUT_FORMAT)) {
            return 'Geschikt voor mensen geboren op ' . $range->getFrom()->format(self::OUTPUT_FORMAT);
        }

        return 'Geschikt voor mensen geboren tussen ' . $range->getFrom()->format(self::OUTPUT_FORMAT) .
            ' en ' . $range->getTo()->format(self::OUTPUT_FORMAT);
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
