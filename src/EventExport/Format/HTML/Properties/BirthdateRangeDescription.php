<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\EventExport\BirthdateRangeFactory;
use stdClass;

final class BirthdateRangeDescription
{
    public static function fromBirthdateRange(stdClass $birthdateRange): ?string
    {
        $range = BirthdateRangeFactory::fromJson($birthdateRange);

        if ($range === null) {
            return null;
        }

        $from = BirthdateRangeFactory::formatDate($range->getFrom());
        $to = BirthdateRangeFactory::formatDate($range->getTo());

        if ($from === $to) {
            return 'Geschikt voor mensen geboren op ' . $from;
        }

        return 'Geschikt voor mensen geboren tussen ' . $from . ' en ' . $to;
    }
}
