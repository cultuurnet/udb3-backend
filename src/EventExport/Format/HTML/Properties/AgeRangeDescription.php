<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\EventExport\AgeRangeFactory;

final class AgeRangeDescription
{
    public static function fromTypicalAgeRange(string $typicalAgeRange): ?string
    {
        $ageRange = AgeRangeFactory::specificFromString($typicalAgeRange);

        if ($ageRange === null) {
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
