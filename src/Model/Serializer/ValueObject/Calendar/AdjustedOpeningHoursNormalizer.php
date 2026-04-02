<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedOpeningHours;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AdjustedOpeningHoursNormalizer implements NormalizerInterface
{
    private OpeningHourNormalizer $openingHourNormalizer;

    public function __construct()
    {
        $this->openingHourNormalizer = new OpeningHourNormalizer();
    }

    /**
     * @param AdjustedOpeningHours $object
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $data = [
            'startDate' => $object->getStartDate()->format('Y-m-d'),
            'endDate' => $object->getEndDate()->format('Y-m-d'),
        ];

        foreach ($object->getOpeningHours()->toArray() as $openingHour) {
            $data['openingHours'][] = $this->openingHourNormalizer->normalize($openingHour);
        }

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
        return $data instanceof AdjustedOpeningHours;
    }
}
