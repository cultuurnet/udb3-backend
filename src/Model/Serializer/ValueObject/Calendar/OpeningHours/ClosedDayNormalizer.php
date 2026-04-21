<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ClosedDayNormalizer implements NormalizerInterface
{
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof ClosedDay;
    }

    /**
     * @param ClosedDay $object
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $data = [
            'startDate' => $object->getStartDate()->format('Y-m-d'),
            'endDate' => $object->getEndDate()->format('Y-m-d'),
        ];

        if ($object->getDescription() !== null) {
            $normalizedDescription = [];
            foreach ($object->getDescription()->getLanguages() as $language) {
                $normalizedDescription[$language->getCode()] = $object->getDescription()->getTranslation($language)->toString();
            }
            $data['description'] = $normalizedDescription;
        }

        return $data;
    }
}
