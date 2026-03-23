<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\ClosedDay;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ClosedDayNormalizer implements NormalizerInterface
{
    public function supportsNormalization($data, $format = null)
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
            $description = $object->getDescription();
            $normalizedDescription = [];
            foreach ($description->getLanguages() as $language) {
                $normalizedDescription[$language->getCode()] = $description->getTranslation($language)->toString();
            }
            $data['description'] = $normalizedDescription;
        }

        return $data;
    }
}
