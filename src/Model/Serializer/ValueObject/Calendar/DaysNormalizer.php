<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DaysNormalizer implements NormalizerInterface
{
    /**
     * @param Days $days
     */
    public function normalize($days, $format = null, array $context = []): array
    {
        return array_map(
            function ($day) {
                return $day->toString();
            },
            $days->toArray()
        );
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Days::class;
    }
}
