<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class BookingAvailabilityDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): BookingAvailability
    {
        return new BookingAvailability(
            new BookingAvailabilityType($data['type'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === BookingAvailability::class;
    }
}
