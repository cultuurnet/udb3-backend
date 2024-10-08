<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Cake\Chronos\Chronos;
use CultureFeed_Cdb_Data_Calendar_Timestamp;
use CultuurNet\UDB3\DateTimeFactory;
use DateTimeInterface;

final class CalendarFactory implements CalendarFactoryInterface
{
    public function createFromCdbCalendar(\CultureFeed_Cdb_Data_Calendar $cdbCalendar): Calendar
    {
        //
        // Get the start day.
        //
        $cdbCalendar->rewind();
        $startDateString = '';
        if ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_PeriodList) {
            /** @var \CultureFeed_Cdb_Data_Calendar_Period $period */
            $period = $cdbCalendar->current();
            $startDateString = $period->getDateFrom() . 'T00:00:00';
        } elseif ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_TimestampList) {
            $firstTimestamp = $cdbCalendar->current();
            $cdbCalendarAsArray = iterator_to_array($cdbCalendar);
            $timestamp = $this->getFirstTimestamp($cdbCalendarAsArray, $firstTimestamp);
            if ($timestamp->getStartTime()) {
                $startDateString = $timestamp->getDate() . 'T' . substr($timestamp->getStartTime(), 0, 5) . ':00';
            } else {
                $startDateString = $timestamp->getDate() . 'T00:00:00';
            }
        }
        $startDate = !empty($startDateString) ? DateTimeFactory::fromCdbFormat($startDateString) : null;

        //
        // Get the end day.
        //
        $cdbCalendar->rewind();
        $endDateString = '';
        if ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_PeriodList) {
            /** @var \CultureFeed_Cdb_Data_Calendar_Period $period */
            $period = $cdbCalendar->current();
            $endDateString = $period->getDateTo() . 'T00:00:00';
        } elseif ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_TimestampList) {
            $firstTimestamp = $cdbCalendar->current();
            /** @var \CultureFeed_Cdb_Data_Calendar_Timestamp $timestamp */
            $cdbCalendarAsArray = iterator_to_array($cdbCalendar);
            $timestamp = $this->getLastTimestamp($cdbCalendarAsArray, $firstTimestamp);
            if ($timestamp->getEndTime()) {
                $endDateString = $timestamp->getDate() . 'T' . $timestamp->getEndTime();
            } else {
                $endTime = $timestamp->getStartTime() ? $timestamp->getStartTime() : '00:00:00';
                $endDateString = $timestamp->getDate() . 'T' . $endTime;
            }
        }
        $endDate = !empty($endDateString) ? DateTimeFactory::fromCdbFormat($endDateString) : null;

        //
        // Get the time stamps.
        //
        $cdbCalendar->rewind();
        $timestamps = [];
        if ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_TimestampList) {
            $splitPeriods = [];
            while ($cdbCalendar->valid()) {
                /** @var \CultureFeed_Cdb_Data_Calendar_Timestamp $timestamp */
                $timestamp = $cdbCalendar->current();
                $cdbCalendar->next();

                $startTime = $timestamp->getStartTime() ? $timestamp->getStartTime() : '00:00:00';
                $startDateString = $timestamp->getDate() . 'T' . $startTime;

                if ($timestamp->getEndTime()) {
                    $endDateString = $timestamp->getDate() . 'T' . $timestamp->getEndTime();
                } else {
                    $endDateString = $timestamp->getDate() . 'T' . $startTime;
                }

                $timestamp = $this->createTimestamp(
                    $startDateString,
                    $endDateString
                );

                $index = (int) ($timestamp->getStartDate()->format('s'));
                if ($index > 0) {
                    $splitPeriods[$index][] = $timestamp;
                } else {
                    $timestamps[] = $timestamp;
                }
            }

            $periods = array_map(
                function (array $periodParts) {
                    $firstPart = array_shift($periodParts);
                    $lastPart = array_pop($periodParts);
                    return new Timestamp(
                        Chronos::instance($firstPart->getStartDate())->second(0),
                        $lastPart ? $lastPart->getEndDate() : $firstPart->getEndDate()
                    );
                },
                $splitPeriods
            );

            $timestamps = array_merge($timestamps, $periods);
        }

        //
        // Get the opening hours.
        //
        $cdbCalendar->rewind();
        $openingHours = [];

        $weekSchema = null;
        if ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_PeriodList) {
            $period = $cdbCalendar->current();
            $weekSchema = $period->getWeekScheme();
        } elseif ($cdbCalendar instanceof  \CultureFeed_Cdb_Data_Calendar_Permanent) {
            $weekSchema = $cdbCalendar->getWeekScheme();
        }

        if ($weekSchema) {
            $openingHours = $this->createOpeningHoursFromWeekScheme($weekSchema);
        }

        if (isset($startDate) && isset($endDate)) {
            $calendarTimeSpan = $this->createChronologicalTimestamp($startDate, $endDate);
        }

        //
        // Get the calendar type.
        //
        $calendarType = null;
        if ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_Permanent) {
            $calendarType = CalendarType::PERMANENT();
        } elseif ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_PeriodList) {
            $calendarType = CalendarType::PERIODIC();
        } elseif ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_TimestampList) {
            $calendarType = CalendarType::SINGLE();
            if (count($timestamps) > 1) {
                $calendarType = CalendarType::MULTIPLE();
            }
        }

        //
        // Create the calendar value object.
        //
        return new Calendar(
            $calendarType,
            isset($calendarTimeSpan) ? $calendarTimeSpan->getStartDate() : null,
            isset($calendarTimeSpan) ? $calendarTimeSpan->getEndDate() : null,
            $timestamps,
            $openingHours
        );
    }

    public function createFromWeekScheme(
        \CultureFeed_Cdb_Data_Calendar_Weekscheme $weekScheme = null
    ): Calendar {
        $openingHours = [];

        if ($weekScheme) {
            $openingHours = $this->createOpeningHoursFromWeekScheme($weekScheme);
        }

        return new Calendar(
            CalendarType::PERMANENT(),
            null,
            null,
            [],
            $openingHours
        );
    }

    /**
     * @return OpeningHour[]
     */
    private function createOpeningHoursFromWeekScheme(
        \CultureFeed_Cdb_Data_Calendar_Weekscheme $weekScheme
    ): array {
        $openingHours = [];

        foreach ($weekScheme->getDays() as $day) {
            if ($day->isOpen()) {
                /** @var \CultureFeed_Cdb_Data_Calendar_OpeningTime[] $openingTimes */
                $openingTimes = $day->getOpeningTimes();

                // A day could be marked as open but without any hours.
                // This means all day open but needs to be mapped to 00:00:00.
                if (count($openingTimes) === 0) {
                    $openingTimes[] = new \CultureFeed_Cdb_Data_Calendar_OpeningTime(
                        '00:00:00',
                        '00:00:00'
                    );
                }

                foreach ($openingTimes as $openingTime) {
                    $opens = DateTimeFactory::fromFormat(
                        'H:i:s',
                        $openingTime->getOpenFrom()
                    );
                    $closes = \DateTime::createFromFormat(
                        'H:i:s',
                        (string) $openingTime->getOpenTill()
                    );

                    $openingHour = new OpeningHour(
                        OpeningTime::fromNativeDateTime($opens),
                        $closes ? OpeningTime::fromNativeDateTime($closes) : OpeningTime::fromNativeDateTime($opens),
                        new DayOfWeekCollection(new DayOfWeek($day->getDayName()))
                    );

                    $openingHours = $this->addToOpeningHours($openingHour, ...$openingHours);
                }
            }
        }

        return $openingHours;
    }

    /**
     * @return OpeningHour[]
     */
    private function addToOpeningHours(
        OpeningHour $newOpeningHour,
        OpeningHour ...$openingHours
    ): array {
        foreach ($openingHours as $openingHour) {
            if ($openingHour->hasEqualHours($newOpeningHour)) {
                $openingHour->addDayOfWeekCollection(
                    $newOpeningHour->getDayOfWeekCollection()
                );
                return $openingHours;
            }
        }

        $openingHours[] = $newOpeningHour;
        return $openingHours;
    }

    private function createTimestamp(
        string $startDateString,
        string $endDateString
    ): Timestamp {
        $startDate = DateTimeFactory::fromCdbFormat($startDateString);
        $endDate = DateTimeFactory::fromCdbFormat($endDateString);

        return $this->createChronologicalTimestamp($startDate, $endDate);
    }

    /**
     * End date might be before start date in cdbxml when event takes place
     * between e.g. 9 PM and 3 AM (the next day). To keep the dates chronological we push the end to the next day.
     *
     * If the end dates does not make any sense at all, it is forced to the start date.
     */
    private function createChronologicalTimestamp(DateTimeInterface $start, DateTimeInterface $end): Timestamp
    {
        $startDate = Chronos::instance($start);
        $endDate = Chronos::instance($end);

        if ($startDate->isSameDay($endDate) && $endDate->lt($startDate)) {
            $endDate = $endDate->addDay();
        }

        if ($endDate->lt($startDate)) {
            $endDate = $startDate;
        }

        return new Timestamp($startDate, $endDate);
    }

    /**
     * @param CultureFeed_Cdb_Data_Calendar_Timestamp[] $timestampList
     */
    private function getLastTimestamp(
        array $timestampList,
        CultureFeed_Cdb_Data_Calendar_Timestamp $default
    ): CultureFeed_Cdb_Data_Calendar_Timestamp {
        $lastTimestamp = $default;
        foreach ($timestampList as $timestamp) {
            $currentEndDate = Chronos::parse($lastTimestamp->getEndDate());
            $endDate = Chronos::parse($timestamp->getEndDate());
            if ($currentEndDate->lt($endDate)) {
                $lastTimestamp = $timestamp;
            }
        }

        return $lastTimestamp;
    }

    /**
     * @param CultureFeed_Cdb_Data_Calendar_Timestamp[] $timestampList
     */
    private function getFirstTimestamp(
        array $timestampList,
        CultureFeed_Cdb_Data_Calendar_Timestamp $default
    ): CultureFeed_Cdb_Data_Calendar_Timestamp {
        $firstTimestamp = $default;
        foreach ($timestampList as $timestamp) {
            $currentStartTime = Chronos::parse($firstTimestamp->getDate());
            $startTime = Chronos::parse($timestamp->getDate());
            if ($currentStartTime->gt($startTime)) {
                $firstTimestamp = $timestamp;
            }
        }

        return $firstTimestamp;
    }
}
