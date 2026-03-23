<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\ClosedDay;

class ClosedDayNormalizer
{
    public function normalize(ClosedDay $closedDay): array
    {
        $data = [
            'startDate' => $closedDay->getStartDate()->format('Y-m-d'),
            'endDate' => $closedDay->getEndDate()->format('Y-m-d'),
        ];

        if ($closedDay->getDescription() !== null) {
            $description = $closedDay->getDescription();
            $normalizedDescription = [];
            foreach ($description->getLanguages() as $language) {
                $normalizedDescription[$language->getCode()] = $description->getTranslation($language)->toString();
            }
            $data['description'] = $normalizedDescription;
        }

        return $data;
    }
}
