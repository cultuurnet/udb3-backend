<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\ChildcareTimeRangeNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\DaysNormalizer;
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

        $childcare = (new ChildcareTimeRangeNormalizer())->normalize($openingHour->getChildcareTimeRange());
        if ($childcare !== null) {
            $normalized['childcare'] = $childcare;
        }

        return $normalized;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof OpeningHour;
    }
}
