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
        $normalized = ['type' => $bookingAvailability->getType()->toString()];

        if ($bookingAvailability->getWaitingListUrl() !== null) {
            $normalized['waitingListUrl'] = $bookingAvailability->getWaitingListUrl()->toString();
        }

        return $normalized;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof BookingAvailability;
    }
}
