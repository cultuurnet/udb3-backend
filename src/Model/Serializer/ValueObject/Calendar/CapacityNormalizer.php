<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Capacity;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CapacityNormalizer implements NormalizerInterface
{
    /**
     * @param Capacity $capacity
     */
    public function normalize($capacity, $format = null, array $context = []): array
    {
        if ($capacity->isUnknown()) {
            return [];
        }

        $normalized = [
            'type' => $capacity->getType()->value,
        ];

        if ($capacity->getMaximum() !== null) {
            $normalized['maximum'] = $capacity->getMaximum();
        }

        if ($capacity->getTaken() !== null) {
            $normalized['taken'] = $capacity->getTaken();
        }

        return $normalized;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Capacity;
    }
}
