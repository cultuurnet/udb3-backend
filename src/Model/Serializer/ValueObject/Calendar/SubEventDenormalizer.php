<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SubEventDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): SubEvent
    {
        $status = new Status(StatusType::Available());
        if (isset($data['status'])) {
            $status =  (new StatusDenormalizer())->denormalize($data['status'], Status::class);
        }

        $bookingAvailability = new BookingAvailability(BookingAvailabilityType::Available());
        if (isset($data['bookingAvailability'])) {
            $bookingAvailability = (new BookingAvailabilityDenormalizer())->denormalize($data['bookingAvailability'], BookingAvailability::class);
        }

        $startDate = DateTimeFactory::fromAtom($data['startDate']);
        $endDate = DateTimeFactory::fromAtom($data['endDate']);

        if ($startDate > $endDate) {
            $endDate = $startDate;
        }

        return new SubEvent(new DateRange($startDate, $endDate), $status, $bookingAvailability);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === SubEvent::class;
    }
}
