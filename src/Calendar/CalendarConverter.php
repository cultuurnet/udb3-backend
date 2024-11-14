<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Cake\Chronos\Chronos;
use CultureFeed_Cdb_Data_Calendar_OpeningTime;
use CultureFeed_Cdb_Data_Calendar_Period;
use CultureFeed_Cdb_Data_Calendar_PeriodList;
use CultureFeed_Cdb_Data_Calendar_Permanent;
use CultureFeed_Cdb_Data_Calendar_SchemeDay;
use CultureFeed_Cdb_Data_Calendar_Timestamp;
use CultureFeed_Cdb_Data_Calendar_TimestampList;
use CultureFeed_Cdb_Data_Calendar_Weekscheme;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use DateInterval;
use DateTimeInterface;
use InvalidArgumentException;
use League\Period\Period;

class CalendarConverter implements CalendarConverterInterface
{
    private \DateTimeZone $cdbTimezone;

    public function __construct(\DateTimeZone $cdbTimezone = null)
    {
        if (is_null($cdbTimezone)) {
            $cdbTimezone = new \DateTimeZone('Europe/Brussels');
        }

        $this->cdbTimezone = $cdbTimezone;
    }

    public function toCdbCalendar(CalendarInterface $calendar): \CultureFeed_Cdb_Data_Calendar
    {
        $weekScheme = $this->getWeekScheme($calendar);
        $calendarType = $calendar->getType()->toString();

        switch ($calendarType) {
            case CalendarType::multiple()->toString():
                $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                $index = 1;
                foreach ($calendar->getSubEvents() as $subEvent) {
                    $currentCount = $this->countTimestamps($cdbCalendar);
                    $cdbCalendar = $this->createTimestampCalendar(
                        $this->configureCdbTimezone($subEvent->getDateRange()->getFrom()),
                        $this->configureCdbTimezone($subEvent->getDateRange()->getTo()),
                        $cdbCalendar,
                        $index
                    );
                    $newCount = $this->countTimestamps($cdbCalendar);
                    if ($currentCount - $newCount !== -1) {
                        $index++;
                    }
                }
                break;
            case CalendarType::single()->toString():
                $cdbCalendar = $this->createTimestampCalendar(
                    $this->configureCdbTimezone($calendar->getStartDate()),
                    $this->configureCdbTimezone($calendar->getEndDate()),
                    new CultureFeed_Cdb_Data_Calendar_TimestampList(),
                    1
                );
                break;
            case CalendarType::periodic()->toString():
                $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_PeriodList();

                $startDate = $this->configureCdbTimezone($calendar->getStartDate())->format('Y-m-d');
                $endDate = $this->configureCdbTimezone($calendar->getEndDate())->format('Y-m-d');

                $period = new CultureFeed_Cdb_Data_Calendar_Period($startDate, $endDate);
                if (!empty($weekScheme) && !empty($weekScheme->getDays())) {
                    $period->setWeekScheme($weekScheme);
                }
                $cdbCalendar->add($period);
                break;
            case CalendarType::permanent()->toString():
                $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_Permanent();
                if (!empty($weekScheme)) {
                    $cdbCalendar->setWeekScheme($weekScheme);
                }
                break;
            default:
                $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_Permanent();
        }

        return $cdbCalendar;
    }

    private function countTimestamps(CultureFeed_Cdb_Data_Calendar_TimestampList $timestamps): int
    {
        $numberOfTimestamps =  iterator_count($timestamps);
        $timestamps->rewind();

        return $numberOfTimestamps;
    }

    /**
     * @throws \Exception
     */
    private function getWeekScheme(CalendarInterface $itemCalendar): ?CultureFeed_Cdb_Data_Calendar_Weekscheme
    {
        // Store opening hours.
        $openingHours = $itemCalendar->getOpeningHours();
        $weekScheme = null;

        if (!empty($openingHours)) {
            $weekScheme = new CultureFeed_Cdb_Data_Calendar_Weekscheme();

            // Multiple opening times can happen on same day. Store them in array.
            $openingTimesPerDay = [
                'monday' => [],
                'tuesday' => [],
                'wednesday' => [],
                'thursday' => [],
                'friday' => [],
                'saturday' => [],
                'sunday' => [],
            ];

            /** @var OpeningHour $openingHour */
            foreach ($openingHours as $openingHour) {
                // In CDB2 every day needs to be a seperate entry.
                /** @var Day $day */
                foreach ($openingHour->getDays()->getIterator() as $day) {
                    $openingTimesPerDay[$day->toString()][] = new CultureFeed_Cdb_Data_Calendar_OpeningTime(
                        $openingHour->getOpeningTime()->toString() . ':00',
                        $openingHour->getClosingTime()->toString() . ':00'
                    );
                }
            }

            // Create the opening times correctly
            foreach ($openingTimesPerDay as $day => $openingTimes) {
                // Empty == closed.
                if (empty($openingTimes)) {
                    $openingInfo = new CultureFeed_Cdb_Data_Calendar_SchemeDay(
                        $day,
                        CultureFeed_Cdb_Data_Calendar_SchemeDay::SCHEMEDAY_OPEN_TYPE_CLOSED
                    );
                } else {
                    // Add all opening times.
                    $openingInfo = new CultureFeed_Cdb_Data_Calendar_SchemeDay(
                        $day,
                        CultureFeed_Cdb_Data_Calendar_SchemeDay::SCHEMEDAY_OPEN_TYPE_OPEN
                    );
                    foreach ($openingTimes as $openingTime) {
                        $openingInfo->addOpeningTime($openingTime);
                    }
                }
                $weekScheme->setDay($day, $openingInfo);
            }
        }

        return $weekScheme;
    }

    private function createTimestampCalendar(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        CultureFeed_Cdb_Data_Calendar_TimestampList $calendar,
        ?int $index = null
    ): CultureFeed_Cdb_Data_Calendar_TimestampList {
        // Make a clone of the original calendar to avoid updating input param.
        $newCalendar = clone $calendar;

        $first24Hours = Period::createFromDuration($startDate, new DateInterval('P1D'));

        // Easy case an no seconds needed for indexing.
        if ($first24Hours->contains($endDate)) {
            $newCalendar->add(
                new CultureFeed_Cdb_Data_Calendar_Timestamp(
                    $startDate->format('Y-m-d'),
                    $this->formatDateTimeAsCdbTime($startDate),
                    $this->formatDateTimeAsCdbTime($endDate)
                )
            );
        } elseif (is_int($index)) {
            // Complex case and seconds needed for indexing.
            $period = new Period($startDate, $endDate);

            $startTimestamp = new CultureFeed_Cdb_Data_Calendar_Timestamp(
                $startDate->format('Y-m-d'),
                $this->formatDateTimeAsCdbTime($startDate, $index)
            );

            $endTimestamp = new CultureFeed_Cdb_Data_Calendar_Timestamp(
                $endDate->format('Y-m-d'),
                $this->createIndexedTimeString($index),
                $this->formatDateTimeAsCdbTime($endDate)
            );

            $untilEndOfSecondDay = new Period(
                $startDate,
                Chronos::instance($startDate)->addDay()->endOfDay()
            );

            if ($untilEndOfSecondDay->contains($endDate)) {
                $fillerTimestamps = [];
            } else {
                $days = iterator_to_array($period->getDatePeriod('1 DAY'));
                $fillerTimestamps = array_map(
                    function (DateTimeInterface $dateTime) use ($index) {
                        return new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            $dateTime->format('Y-m-d'),
                            $this->createIndexedTimeString($index)
                        );
                    },
                    array_slice($days, 1, count($days) === 2 ? 2 : -1)
                );
            }

            $newCalendar = array_reduce(
                array_merge([$startTimestamp], $fillerTimestamps, [$endTimestamp]),
                function (CultureFeed_Cdb_Data_Calendar_TimestampList $calendar, $timestamp) {
                    $calendar->add($timestamp);
                    return $calendar;
                },
                $newCalendar
            );
        }

        return $newCalendar;
    }

    private function formatDateTimeAsCdbTime(DateTimeInterface $timestamp, ?int $index = null): string
    {
        if (is_int($index) && $index > 59) {
            throw new InvalidArgumentException('The CDB time index should not be higher than 59!');
        }

        return is_int($index)
            ? $timestamp->format('H:i') . ':' . str_pad((string) $index, 2, '0', STR_PAD_LEFT)
            : $timestamp->format('H:i:s');
    }

    private function createIndexedTimeString(int $index): string
    {
        return '00:00:' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }

    /**
     * DateTimeInterface has no setTimezone() method, so we need to convert it to a DateTimeImmutable object
     * first using Chronos.
     */
    private function configureCdbTimezone(\DateTimeInterface $dateTime): DateTimeInterface
    {
        return Chronos::instance($dateTime)->setTimezone($this->cdbTimezone);
    }
}
