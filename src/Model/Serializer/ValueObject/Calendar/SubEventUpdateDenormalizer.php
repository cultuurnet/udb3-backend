<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use DateTimeImmutable;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SubEventUpdateDenormalizer implements DenormalizerInterface
{
    private StatusDenormalizer $statusDenormalizer;
    private BookingAvailabilityDenormalizer $bookingAvailabilityDenormalizer;

    public function __construct()
    {
        $this->statusDenormalizer = new StatusDenormalizer();
        $this->bookingAvailabilityDenormalizer = new BookingAvailabilityDenormalizer();
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $subEventUpdate = new SubEventUpdate((int)$data['id']);

        if (isset($data['startDate'])) {
            $subEventUpdate = $subEventUpdate->withStartDate(
                new DateTimeImmutable($data['startDate'])
            );
        }

        if (isset($data['endDate'])) {
            $subEventUpdate = $subEventUpdate->withEndDate(
                new DateTimeImmutable($data['endDate'])
            );
        }

        if (isset($data['status'])) {
            $subEventUpdate = $subEventUpdate->withStatus(
                $this->statusDenormalizer->denormalize($data['status'], Status::class)
            );
        }
        if (isset($data['bookingAvailability'])) {
            $subEventUpdate = $subEventUpdate->withBookingAvailability(
                $this->bookingAvailabilityDenormalizer->denormalize($data['bookingAvailability'], BookingAvailability::class)
            );
        }

        return $subEventUpdate;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === SubEventUpdate::class;
    }
}
