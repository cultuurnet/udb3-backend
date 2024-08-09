<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Parser;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;

final class KinepolisDateParser implements DateParser
{
    // This class converts the Json received from the external MovieAPI,
    // which has a hierarchy that is divided as follows:
    // - movie (e.g., "Het smelt")
    // - date (e.g., 2024-04-09)
    // - screeningTime (e.g. 19:45:00)
    // - version (2D or 3d)
    // into a hierarchy that is better suited to convert them to UiTDatabank events by changing it to a
    // - LocationId
    // - version
    // - DateTimeList
    // hierarchy
    private array $timeTableList;

    // This function receives and array from the external API,
    // with dates as key, and then an array of info with the dimension,
    // location, and Time.
    // For compatibility with our API, we transform this in an associative array
    // with as key the Location, the dimension as second-level Key which contains
    // an array of Timestamps.
    // TODO: Look for a way to make a typed Object(refactor ParsedMovie?) and return those.
    public function processDates(array $dates, int $length): array
    {
        $this->timeTableList = [];

        foreach ($dates as $day => $timeList) {
            $this->processDay($day, $timeList, $length);
        }

        return $this->timeTableList;
    }

    private function processDay(string $day, array $timeList, int $length): void
    {
        foreach ($timeList as $info) {
            $is3D = $this->is3D($info['format']);
            $from = $this->getFromTime($day, $info['time']);
            $to = $this->getToTime($from, $length);
            $this->timeTableList[$info['tid']][$is3D ? '3D' : '2D'][] = new SubEvent(
                new DateRange($from, $to),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available())
            );
        }
    }

    private function getToTime(\DateTimeImmutable $from, int $length): \DateTimeImmutable
    {
        return $from->add(new \DateInterval('PT' . $length . 'M'));
    }

    private function getFromTime(string $day, string $time): \DateTimeImmutable
    {
        $timeZoneBrussels = new \DateTimeZone('Europe/Brussels');
        $timeZoneUtc = new \DateTimeZone('UTC');
        $dt = DateTimeFactory::fromFormat('Y-m-d H:i:s', $day . ' ' . $time, $timeZoneBrussels);
        return $dt->setTimezone($timeZoneUtc);
    }

    private function is3D(array $formats): bool
    {
        // These "magic" numbers are all the ids which are 3D screenings in the external taxonomy.
        $formats3D = [
            52,
            53,
            54,
            740,
            1033,
            1035,
            1036,
            1037,
            1045,
            1070,
            1093,
            1145,
            1147,
        ];
        return count(array_intersect($formats, $formats3D)) > 0;
    }
}
