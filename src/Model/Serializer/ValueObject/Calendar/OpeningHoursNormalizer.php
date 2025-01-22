<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OpeningHoursNormalizer implements NormalizerInterface
{
    /**
     * @param OpeningHours $openingHours
     */
    public function normalize($openingHours, $format = null, array $context = []): array
    {
        $output = [];
        foreach ($openingHours as $openingHour) {
            $output[] = (new OpeningHourNormalizer())->normalize($openingHour);
        }
        return $output;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof OpeningHours;
    }
}
