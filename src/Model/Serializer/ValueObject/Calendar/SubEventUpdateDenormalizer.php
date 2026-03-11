<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use DateTimeImmutable;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SubEventUpdateDenormalizer implements DenormalizerInterface
{
    private StatusDenormalizer $statusDenormalizer;
    private BookingAvailabilityDenormalizer $bookingAvailabilityDenormalizer;
    private BookingInfoDenormalizer $bookingInfoDenormalizer;

    public function __construct()
    {
        $this->statusDenormalizer = new StatusDenormalizer();
        $this->bookingAvailabilityDenormalizer = new BookingAvailabilityDenormalizer();
        $this->bookingInfoDenormalizer = new BookingInfoDenormalizer();
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
                $this->bookingAvailabilityDenormalizer->denormalize(
                    $data['bookingAvailability'],
                    BookingAvailability::class
                )
            );
        }
        if (isset($data['bookingInfo'])) {
            $subEventUpdate = $subEventUpdate->withBookingInfo(
                $this->bookingInfoDenormalizer->denormalize($data['bookingInfo'], BookingInfo::class)
            );
        }

        if (isset($data['childcare'])) {
            $subEventUpdate = $subEventUpdate->withChildcareTimeRange(
                $this->denormalizeChildcare($data['childcare'])
            );
        }

        return $subEventUpdate;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === SubEventUpdate::class;
    }

    /**
     * Resolves the PATCH childcare semantics:
     * - absent key          → preserve (caller must not call this method)
     * - non-array value     → clear  (e.g. null sent explicitly)
     * - empty array {}      → clear
     * - array with start/end → set (either or both fields may be present)
     */
    private function denormalizeChildcare(array $childcare): ?TimeImmutableRange
    {
        $start = $childcare['start'] ?? null;
        $end = $childcare['end'] ?? null;

        if ($start === null && $end === null) {
            return null;
        }

        return new TimeImmutableRange($start, $end);
    }
}
