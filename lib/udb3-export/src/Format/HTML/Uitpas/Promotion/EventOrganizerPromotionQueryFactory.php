<?php


namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion;

use CultureFeed_Uitpas_Event_CultureEvent;
use CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions;
use CultureFeed_Uitpas_Calendar;
use CultureFeed_Uitpas_Calendar_Period;
use CultuurNet\Clock\Clock;

class EventOrganizerPromotionQueryFactory implements PromotionQueryFactoryInterface
{
    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param Clock $clock
     */
    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    /**
     * @inheritdoc
     */
    public function createForEvent(
        CultureFeed_Uitpas_Event_CultureEvent $event
    ) {
        /** @var CultureFeed_Uitpas_Calendar $eventCalendar */
        $eventCalendar = $event->calendar;
        if ($eventCalendar) {
            $dateRange = $this->getDateRangeFromUitpasCalendar($eventCalendar);
        } else {
            $dateRange = new CultureFeed_Uitpas_Calendar_Period();
            $dateRange->datefrom = time();
        }

        $promotionsQuery = new \CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions();
        $promotionsQuery->balieConsumerKey = $event->organiserId;
        $promotionsQuery->cashingPeriodBegin = $dateRange->datefrom;
        if ($dateRange->dateto) {
            $promotionsQuery->cashingPeriodEnd = $dateRange->dateto;
        }
        $promotionsQuery->unexpired = true;
        $promotionsQuery->max = 2;

        return $promotionsQuery;
    }

    /**
     * @param CultureFeed_Uitpas_Calendar $uitpasCalendar
     * @return CultureFeed_Uitpas_Calendar_Period
     */
    private function getDateRangeFromUitpasCalendar(CultureFeed_Uitpas_Calendar $uitpasCalendar)
    {
        $dateRange = new CultureFeed_Uitpas_Calendar_Period();

        if (!empty($uitpasCalendar->periods)) {
            /** @var CultureFeed_Uitpas_Calendar_Period $firstPeriod */
            $firstPeriod = reset($uitpasCalendar->periods);
            $dateRange->datefrom = $firstPeriod->datefrom;

            /** @var CultureFeed_Uitpas_Calendar_Period $lastPeriod */
            $lastPeriod =  end($uitpasCalendar->periods);
            $dateRange->dateto = $lastPeriod->dateto;
        } elseif (!empty($uitpasCalendar->timestamps)) {
            /**
             * The custom Timestamp format for these UiTPAS calendars is a pain
             * to work with. I pick the start and end of the day to determine the
             * actual timestamps. This way events that only span one day
             * are also covered
             */
            /** @var \CultureFeed_Uitpas_Calendar_Timestamp $firstPeriod */
            $firstTimestamp = reset($uitpasCalendar->timestamps);
            $firstTimestampDate = new \DateTime();
            $firstTimestampDate
              ->setTimestamp($firstTimestamp->date)
              ->setTime(0, 0, 0);
            $dateRange->datefrom = $firstTimestampDate->getTimestamp();

            /** @var \CultureFeed_Uitpas_Calendar_Timestamp $lastTimestamp */
            $lastTimestamp =  end($uitpasCalendar->timestamps);
            $lastTimestampDate = new \DateTime();
            $lastTimestampDate
              ->setTimestamp($lastTimestamp->date)
              ->setTime(24, 59, 59);
            $dateRange->dateto = $lastTimestampDate->getTimestamp();
        } else {
            // If there is no useful calendar info, start from the time the
            // export was created.
            $now = $this->clock->getDateTime();
            $dateRange->datefrom = $now->getTimestamp();
        }

        return $dateRange;
    }
}
