<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Capacity;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CapacityType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CapacityDenormalizer implements DenormalizerInterface
{
    /**
     * An empty object ({}) denormalizes to an unknown capacity, which clears the capacity of the subEvent.
     */
    public function denormalize($data, $class, $format = null, array $context = []): Capacity
    {
        if (!isset($data['type'])) {
            return Capacity::unknown();
        }

        $taken = isset($data['taken']) ? (int) $data['taken'] : null;

        if (CapacityType::from($data['type']) === CapacityType::Limited) {
            return Capacity::limited((int) $data['maximum'], $taken);
        }

        return Capacity::unlimited($taken);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Capacity::class;
    }
}
