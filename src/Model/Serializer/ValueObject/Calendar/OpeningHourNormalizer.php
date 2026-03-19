<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OpeningHourNormalizer implements NormalizerInterface
{
    /**
     * @param OpeningHour $openingHour
     */
    public function normalize($openingHour, $format = null, array $context = []): array
    {
        $normalized = [
            'opens' => $openingHour->getOpeningTime()->toString(),
            'closes' => $openingHour->getClosingTime()->toString(),
            'dayOfWeek' => (new DaysNormalizer())->normalize($openingHour->getDays()),
        ];

        $childcareTimeRange = $openingHour->getChildcareTimeRange();
        if ($childcareTimeRange !== null) {
            $start = $childcareTimeRange->getStart()?->getValue();
            $end = $childcareTimeRange->getEnd()?->getValue();
            if ($start !== null || $end !== null) {
                $childcare = [];
                if ($start !== null) {
                    $childcare['start'] = $start;
                }
                if ($end !== null) {
                    $childcare['end'] = $end;
                }
                $normalized['childcare'] = $childcare;
            }
        }

        return $normalized;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof OpeningHour;
    }
}
