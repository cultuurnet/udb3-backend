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
        $bookingAvailability = new BookingAvailability($this->getType($data));

        if (isset($data['capacity'])) {
            $bookingAvailability = $bookingAvailability->withCapacity($data['capacity']);
        }

        if (isset($data['availability'])) {
            $bookingAvailability = $bookingAvailability->withAvailability($data['availability']);
        }

        return $bookingAvailability;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === BookingAvailability::class;
    }

    private function getType(array $data): BookingAvailabilityType
    {
        if (isset($data['availability'])) {
            return $data['availability'] > 0
                ? BookingAvailabilityType::Available()
                : BookingAvailabilityType::Unavailable();
        }

        return new BookingAvailabilityType($data['type']);
    }
}
