<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

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
            $format = $this->getFormat($info['format']);
            $from = $this->getFromTime($day, $info['time']);
            $to = $this->getToTime($from, $length);
            $this->timeTableList[$info['tid']][$format][] = new SubEvent(
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
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $day . ' ' . $time, $timeZoneBrussels);
        return $dt->setTimezone($timeZoneUtc);
    }

    private function getFormat(array $formats): string
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
        return sizeof(array_intersect($formats, $formats3D)) === 0 ? '2D' : '3D';
    }
}
