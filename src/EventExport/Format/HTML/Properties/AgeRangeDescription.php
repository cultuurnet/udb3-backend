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
        } catch (InvalidAgeRangeException) {
            return null;
        }

        if ($ageRange->toString() === '-') {
            return null;
        }

        $from = $ageRange->getFrom()?->toInteger();
        $to = $ageRange->getTo()?->toInteger();

        if ($to === null) {
            return 'Geschikt voor ' . $from . ' jaar en ouder';
        }

        if ($from === $to) {
            return 'Geschikt voor ' . $from . ' jaar';
        }

        if ($from === 0) {
            return 'Geschikt tot ' . $to . ' jaar';
        }

        return 'Geschikt voor ' . $from . ' tot ' . $to . ' jaar';
    }
}
