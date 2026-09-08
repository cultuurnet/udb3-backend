<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\EventExport\BirthdateRangeFactory;
use stdClass;

final class BirthdateRangeDescription
{
    public static function fromBirthdateRange(stdClass $birthdateRange): ?string
    {
        $range = BirthdateRangeFactory::fromProjection($birthdateRange);

        if ($range === null) {
            return null;
        }

        $from = $range->getFrom()->format(BirthdateRangeFactory::DISPLAY_FORMAT);
        $to = $range->getTo()->format(BirthdateRangeFactory::DISPLAY_FORMAT);

        if ($from === $to) {
            return 'Geschikt voor mensen geboren op ' . $from;
        }

        return 'Geschikt voor mensen geboren tussen ' . $from . ' en ' . $to;
    }
}
