<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\Calendar\Timestamp;

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

        return BookingAvailability::deserialize($data['bookingAvailability']);
    }

    /**
     * @return Timestamp[]
     */
    public function getTimestamps(array $data): array
    {
        if (empty($data['timeSpans'])) {
            return [];
        }

        $timestamps = [];
        foreach ($data['timeSpans'] as $index => $timeSpan) {
            if (empty($timeSpan['start']) || empty($timeSpan['end'])) {
                continue;
            }

            $timestamp = new Timestamp(new \DateTime($timeSpan['start']), new \DateTime($timeSpan['end']));

            $status = isset($timeSpan['status']) ? (new StatusDenormalizer())->denormalize($timeSpan['status'], Status::class) : $this->getStatus($data);
            if ($status) {
                $timestamp = $timestamp->withStatus(new Status($status->getType(), $status->getReason()));
            }

            $bookingAvailability = isset($timeSpan['bookingAvailability']) ?
                BookingAvailability::deserialize($timeSpan['bookingAvailability']) : $this->getBookingAvailability($data);
            if ($bookingAvailability) {
                $timestamp = $timestamp->withBookingAvailability($bookingAvailability->toUdb3ModelBookingAvailability());
            }

            $timestamps[] = $timestamp;
        }

        ksort($timestamps);

        return $timestamps;
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
                OpeningTime::fromNativeString($openingHour['opens']),
                OpeningTime::fromNativeString($openingHour['closes']),
                DayOfWeekCollection::deserialize($openingHour['dayOfWeek'])
            );
        }

        return $openingHours;
    }
}
