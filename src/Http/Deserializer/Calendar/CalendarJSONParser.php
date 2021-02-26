<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Timestamp;

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

        return Status::deserialize($data['status']);
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

            $status = isset($timeSpan['status']) ? Status::deserialize($timeSpan['status']) : $this->getStatus($data);
            if ($status) {
                $timestamp = $timestamp->withStatus($status);
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
