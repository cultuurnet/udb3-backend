<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
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

        $bookingInfo = new BookingInfo();
        if (isset($data['bookingInfo'])) {
            $bookingInfo = (new BookingInfoDenormalizer())->denormalize($data['bookingInfo'], BookingInfo::class);
        }

        $startDate = DateTimeFactory::fromAtom($data['startDate']);
        $endDate = DateTimeFactory::fromAtom($data['endDate']);

        if ($startDate > $endDate) {
            $endDate = $startDate;
        }

        $subEvent = new SubEvent(new DateRange($startDate, $endDate), $status, $bookingAvailability, $bookingInfo);

        $childcareStart = $data['childcare']['start'] ?? null;
        $childcareEnd = $data['childcare']['end'] ?? null;

        if ($childcareStart !== null || $childcareEnd !== null) {
            $start = $childcareStart !== null ? new Time($childcareStart) : null;
            $end = $childcareEnd !== null ? new Time($childcareEnd) : null;
            $subEvent = $subEvent->withChildcareTimeRange(new TimeImmutableRange($start, $end));
        }

        return $subEvent;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === SubEvent::class;
    }
}
