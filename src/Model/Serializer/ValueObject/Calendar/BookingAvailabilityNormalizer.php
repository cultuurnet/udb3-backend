<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class BookingAvailabilityNormalizer implements NormalizerInterface
{
    /**
     * @param BookingAvailability $bookingAvailability
     */
    public function normalize($bookingAvailability, $format = null, array $context = []): array
    {
        return [
            'type' => $bookingAvailability->getType()->toString(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === BookingAvailability::class;
    }
}
