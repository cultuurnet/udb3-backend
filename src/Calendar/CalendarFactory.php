<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Cake\Chronos\Chronos;
use CultureFeed_Cdb_Data_Calendar_Timestamp;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use DateTimeImmutable;
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
                $endTime = $timestamp->getStartTime() ?: '00:00:00';
                $endDateString = $timestamp->getDate() . 'T' . $endTime;
            }
        }
        $endDate = !empty($endDateString) ? DateTimeFactory::fromCdbFormat($endDateString) : null;

        //
        // Get the time stamps.
        //
        $cdbCalendar->rewind();
        $subEvents = [];
        if ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_TimestampList) {
            $splitPeriods = [];
            while ($cdbCalendar->valid()) {
                /** @var \CultureFeed_Cdb_Data_Calendar_Timestamp $timestamp */
                $timestamp = $cdbCalendar->current();
                $cdbCalendar->next();

                $startTime = $timestamp->getStartTime() ?: '00:00:00';
                $startDateString = $timestamp->getDate() . 'T' . $startTime;

                if ($timestamp->getEndTime()) {
                    $endDateString = $timestamp->getDate() . 'T' . $timestamp->getEndTime();
                } else {
                    $endDateString = $timestamp->getDate() . 'T' . $startTime;
                }

                $subEvent = $this->createSubEvent(
                    $startDateString,
                    $endDateString
                );

                $index = (int) ($subEvent->getDateRange()->getFrom()->format('s'));
                if ($index > 0) {
                    $splitPeriods[$index][] = $subEvent;
                } else {
                    $subEvents[] = $subEvent;
                }
            }

            $periods = array_map(
                function (array $periodParts): SubEvent {
                    /** @var SubEvent[] $periodParts */
                    $firstPart = array_shift($periodParts);
                    $lastPart = array_pop($periodParts);
                    return SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable(Chronos::instance($firstPart->getDateRange()->getFrom())->second(0)->toAtomString()),
                            $lastPart ? $lastPart->getDateRange()->getTo() : $firstPart->getDateRange()->getTo()
                        )
                    );
                },
                $splitPeriods
            );

            $subEvents = array_merge($subEvents, $periods);
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
            $calendarTimeSpan = $this->createChronologicalSubEvent($startDate, $endDate);
        }

        //
        // Get the calendar type.
        //
        $calendarType = null;
        if ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_Permanent) {
            $calendarType = CalendarType::permanent();
        } elseif ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_PeriodList) {
            $calendarType = CalendarType::periodic();
        } elseif ($cdbCalendar instanceof \CultureFeed_Cdb_Data_Calendar_TimestampList) {
            $calendarType = CalendarType::single();
            if (count($subEvents) > 1) {
                $calendarType = CalendarType::multiple();
            }
        }

        //
        // Create the calendar value object.
        //
        return new Calendar(
            $calendarType,
            isset($calendarTimeSpan) ? $calendarTimeSpan->getDateRange()->getFrom() : null,
            isset($calendarTimeSpan) ? $calendarTimeSpan->getDateRange()->getTo() : null,
            $subEvents,
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
            CalendarType::permanent(),
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
                        new Days(new Day($day->getDayName())),
                        Time::fromDateTime($opens),
                        $closes ? Time::fromDateTime($closes) : Time::fromDateTime($opens)
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
                $openingHour->addDays($newOpeningHour->getDays());
                return $openingHours;
            }
        }

        $openingHours[] = $newOpeningHour;
        return $openingHours;
    }

    private function createSubEvent(
        string $startDateString,
        string $endDateString
    ): SubEvent {
        $startDate = DateTimeFactory::fromCdbFormat($startDateString);
        $endDate = DateTimeFactory::fromCdbFormat($endDateString);

        return $this->createChronologicalSubEvent($startDate, $endDate);
    }

    /**
     * End date might be before start date in cdbxml when event takes place
     * between e.g. 9 PM and 3 AM (the next day). To keep the dates chronological we push the end to the next day.
     *
     * If the end dates does not make any sense at all, it is forced to the start date.
     */
    private function createChronologicalSubEvent(DateTimeInterface $start, DateTimeInterface $end): SubEvent
    {
        $startDate = Chronos::instance($start);
        $endDate = Chronos::instance($end);

        if ($startDate->isSameDay($endDate) && $endDate->lt($startDate)) {
            $endDate = $endDate->addDay();
        }

        if ($endDate->lt($startDate)) {
            $endDate = $startDate;
        }

        return SubEvent::createAvailable(
            new DateRange(
                new DateTimeImmutable($startDate->toAtomString()),
                new DateTimeImmutable($endDate->toAtomString())
            )
        );
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
