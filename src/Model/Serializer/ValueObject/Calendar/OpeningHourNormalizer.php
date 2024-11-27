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
        return [
            'opens' => $openingHour->getOpeningTime()->toString(),
            'closes' => $openingHour->getClosingTime()->toString(),
            'dayOfWeek' => (new DaysNormalizer())->normalize($openingHour->getDays()),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === OpeningHour::class;
    }
}
