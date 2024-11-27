<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\BookingAvailabilityDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\DaysDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;

class CalendarJSONParser
{
    public function getStartDate(array $data): ?\DateTimeInterface
    {
        if (!isset($data['startDate'])) {
            return null;
        }

        return new \DateTime($data['startDate']);
    }

    public function getEndDate(array $data): ?\DateTimeInterface
    {
        if (!isset($data['endDate'])) {
            return null;
        }

        return new \DateTime($data['endDate']);
    }

    public function getStatus(array $data): ?Status
    {
        if (!isset($data['status'])) {
            return null;
        }

        return (new StatusDenormalizer())->denormalize($data['status'], Status::class);
    }

    public function getBookingAvailability(array $data): ?BookingAvailability
    {
        if (!isset($data['bookingAvailability'])) {
            return null;
        }

        return (new BookingAvailabilityDenormalizer())->denormalize($data['bookingAvailability'], BookingAvailability::class);
    }

    /**
     * @return SubEvent[]
     */
    public function getSubEvents(array $data): array
    {
        if (empty($data['timeSpans'])) {
            return [];
        }

        $subEvents = [];
        foreach ($data['timeSpans'] as $timeSpan) {
            if (empty($timeSpan['start']) || empty($timeSpan['end'])) {
                continue;
            }

            $subEvent = SubEvent::createAvailable(
                new DateRange(new \DateTimeImmutable($timeSpan['start']), new \DateTimeImmutable($timeSpan['end']))
            );

            $status = isset($timeSpan['status']) ? (new StatusDenormalizer())->denormalize($timeSpan['status'], Status::class) : $this->getStatus($data);
            if ($status) {
                $subEvent = $subEvent->withStatus(new Status($status->getType(), $status->getReason()));
            }

            $bookingAvailability = isset($timeSpan['bookingAvailability']) ?
                (new BookingAvailabilityDenormalizer())->denormalize($timeSpan['bookingAvailability'], BookingAvailability::class) : $this->getBookingAvailability($data);
            if ($bookingAvailability) {
                $subEvent = $subEvent->withBookingAvailability($bookingAvailability);
            }

            $subEvents[] = $subEvent;
        }

        ksort($subEvents);

        return $subEvents;
    }

    /**
     * @return OpeningHour[]
     */
    public function getOpeningHours(array $data): array
    {
        if (empty($data['openingHours'])) {
            return [];
        }

        $openingHours = [];
        foreach ($data['openingHours'] as $openingHour) {
            if (empty($openingHour['dayOfWeek']) || empty($openingHour['opens']) || empty($openingHour['closes'])) {
                continue;
            }

            $openingHours[] = new OpeningHour(
                (new DaysDenormalizer())->denormalize($openingHour['dayOfWeek'], Days::class),
                Time::fromString($openingHour['opens']),
                Time::fromString($openingHour['closes'])
            );
        }

        return $openingHours;
    }
}
