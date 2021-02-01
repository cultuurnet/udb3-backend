<?php

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
use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\CalendarType;
use DateInterval;
use DateTimeInterface;
use InvalidArgumentException;
use League\Period\Period;

class CalendarConverter implements CalendarConverterInterface
{
    /**
     * @var \DateTimeZone
     */
    private $cdbTimezone;

    public function __construct(\DateTimeZone $cdbTimezone = null)
    {
        if (is_null($cdbTimezone)) {
            $cdbTimezone = new \DateTimeZone('Europe/Brussels');
        }

        $this->cdbTimezone = $cdbTimezone;
    }

    /**
     * @inheritdoc
     */
    public function toCdbCalendar(CalendarInterface $calendar)
    {
        $weekScheme = $this->getWeekScheme($calendar);
        $calendarType = (string) $calendar->getType();

        switch ($calendarType) {
            case CalendarType::MULTIPLE:
                $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                $index = 1;
                foreach ($calendar->getTimestamps() as $timestamp) {
                    $currentCount = $this->countTimestamps($cdbCalendar);
                    $cdbCalendar = $this->createTimestampCalendar(
                        $this->configureCdbTimezone($timestamp->getStartDate()),
                        $this->configureCdbTimezone($timestamp->getEndDate()),
                        $cdbCalendar,
                        $index
                    );
                    $newCount = $this->countTimestamps($cdbCalendar);
                    if ($currentCount - $newCount !== -1) {
                        $index++;
                    }
                }
                break;
            case CalendarType::SINGLE:
                $cdbCalendar = $this->createTimestampCalendar(
                    $this->configureCdbTimezone($calendar->getStartDate()),
                    $this->configureCdbTimezone($calendar->getEndDate()),
                    new CultureFeed_Cdb_Data_Calendar_TimestampList(),
                    1
                );
                break;
            case CalendarType::PERIODIC:
                $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_PeriodList();

                $startDate = $this->configureCdbTimezone($calendar->getStartDate())->format('Y-m-d');
                $endDate = $this->configureCdbTimezone($calendar->getEndDate())->format('Y-m-d');

                $period = new CultureFeed_Cdb_Data_Calendar_Period($startDate, $endDate);
                if (!empty($weekScheme) && !empty($weekScheme->getDays())) {
                    $period->setWeekScheme($weekScheme);
                }
                $cdbCalendar->add($period);
                break;
            case CalendarType::PERMANENT:
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

    /**
     * @param CultureFeed_Cdb_Data_Calendar_TimestampList $timestamps
     * @return int
     */
    private function countTimestamps(CultureFeed_Cdb_Data_Calendar_TimestampList $timestamps)
    {
        $numberOfTimestamps =  iterator_count($timestamps);
        $timestamps->rewind();

        return $numberOfTimestamps;
    }

    /**
     * @param \CultuurNet\UDB3\CalendarInterface $itemCalendar
     * @return CultureFeed_Cdb_Data_Calendar_Weekscheme|null
     * @throws \Exception
     */
    private function getWeekScheme(CalendarInterface $itemCalendar)
    {
        // Store opening hours.
        $openingHours = $itemCalendar->getOpeningHours();
        $weekScheme = null;

        if (!empty($openingHours)) {
            $weekScheme = new CultureFeed_Cdb_Data_Calendar_Weekscheme();

            // Multiple opening times can happen on same day. Store them in array.
            $openingTimesPerDay = array(
                'monday' => array(),
                'tuesday' => array(),
                'wednesday' => array(),
                'thursday' => array(),
                'friday' => array(),
                'saturday' => array(),
                'sunday' => array(),
            );

            foreach ($openingHours as $openingHour) {
                // In CDB2 every day needs to be a seperate entry.
                if (is_array($openingHour)) {
                    $openingHour = (object) $openingHour;
                }
                foreach ($openingHour->getDayOfWeekCollection()->getDaysOfWeek() as $day) {
                    $openingTimesPerDay[$day->toNative()][] = new CultureFeed_Cdb_Data_Calendar_OpeningTime(
                        $openingHour->getOpens()->toNativeString() . ':00',
                        $openingHour->getCloses()->toNativeString() . ':00'
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

    /**
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @param CultureFeed_Cdb_Data_Calendar_TimestampList $calendar
     * @param Integer|null $index
     *
     * @return CultureFeed_Cdb_Data_Calendar_TimestampList
     */
    private function createTimestampCalendar(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        CultureFeed_Cdb_Data_Calendar_TimestampList $calendar,
        $index = null
    ) {
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

    /**
     * @param DateTimeInterface $timestamp
     * @param integer|null $index
     * @return null|string
     */
    private function formatDateTimeAsCdbTime(DateTimeInterface $timestamp, $index = null)
    {
        if (is_int($index) && $index > 59) {
            throw new InvalidArgumentException('The CDB time index should not be higher than 59!');
        }

        $time = is_int($index)
            ? $timestamp->format('H:i') . ':' . str_pad((string) $index, 2, '0', STR_PAD_LEFT)
            : $timestamp->format('H:i:s');

        return $time;
    }

    /**
     * @param int $index
     * @return string
     */
    private function createIndexedTimeString($index)
    {
        return '00:00:' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }

    /**
     * DateTimeInterface has no setTimezone() method, so we need to convert it to a DateTimeImmutable object
     * first using Chronos.
     *
     * @param DateTimeInterface $dateTime
     * @return DateTimeInterface
     */
    private function configureCdbTimezone(\DateTimeInterface $dateTime)
    {
        return Chronos::instance($dateTime)->setTimezone($this->cdbTimezone);
    }
}
