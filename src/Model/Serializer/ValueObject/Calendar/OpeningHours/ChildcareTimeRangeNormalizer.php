<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;

final class ChildcareTimeRangeNormalizer
{
    /**
     * @return array<string, string>|null
     */
    public function normalize(?TimeImmutableRange $childcareTimeRange): ?array
    {
        if ($childcareTimeRange === null) {
            return null;
        }

        $start = $childcareTimeRange->getStart()?->getValue();
        $end = $childcareTimeRange->getEnd()?->getValue();

        if ($start === null && $end === null) {
            return null;
        }

        $childcare = [];
        if ($start !== null) {
            $childcare['start'] = $start;
        }
        if ($end !== null) {
            $childcare['end'] = $end;
        }

        return $childcare;
    }
}
