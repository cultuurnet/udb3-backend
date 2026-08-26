<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\InvalidAgeRangeException;

final class AgeRangeDescription
{
    public static function fromTypicalAgeRange(string $typicalAgeRange): ?string
    {
        try {
            $ageRange = AgeRange::fromString($typicalAgeRange);
        } catch (InvalidAgeRangeException $exception) {
            return null;
        }

        $from = $ageRange->getFrom() ? $ageRange->getFrom()->toInteger() : null;
        $to = $ageRange->getTo() ? $ageRange->getTo()->toInteger() : null;

        if ($to === null && ($from === null || $from === 0)) {
            return null;
        }

        if ($to === null) {
            return 'Geschikt voor ' . $from . ' jaar en ouder';
        }

        if ($from === null || $from === 0) {
            return 'Geschikt tot ' . $to . ' jaar';
        }

        if ($from === $to) {
            return 'Geschikt voor ' . $from . ' jaar';
        }

        return 'Geschikt voor ' . $from . ' tot ' . $to . ' jaar';
    }
}
