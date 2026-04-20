<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AdjustedDayNormalizer implements NormalizerInterface
{
    private OpeningHourNormalizer $openingHourNormalizer;

    public function __construct()
    {
        $this->openingHourNormalizer = new OpeningHourNormalizer();
    }

    /**
     * @param AdjustedDay $object
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $data = [
            'startDate' => $object->getStartDate()->format('Y-m-d'),
            'endDate' => $object->getEndDate()->format('Y-m-d'),
        ];

        $data['openingHours'] = array_map(
            fn (OpeningHour $openingHour) => $this->openingHourNormalizer->normalize($openingHour),
            $object->getOpeningHours()->toArray()
        );

        if ($object->getDescription() !== null) {
            $normalizedDescription = [];
            foreach ($object->getDescription()->getLanguages() as $language) {
                $normalizedDescription[$language->getCode()] = $object->getDescription()->getTranslation($language)->toString();
            }
            $data['description'] = $normalizedDescription;
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof AdjustedDay;
    }
}
