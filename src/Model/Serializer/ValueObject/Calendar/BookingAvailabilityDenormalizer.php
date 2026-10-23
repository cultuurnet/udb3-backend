<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class BookingAvailabilityDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): BookingAvailability
    {
        $bookingAvailability = new BookingAvailability(
            new BookingAvailabilityType($data['type'])
        );

        // An empty string is how a sub event PATCH clears the waiting list url, so it means no url here.
        if (!empty($data['waitingListUrl'])) {
            $bookingAvailability = $bookingAvailability->withWaitingListUrl(new Url($data['waitingListUrl']));
        }

        return $bookingAvailability;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === BookingAvailability::class;
    }
}
