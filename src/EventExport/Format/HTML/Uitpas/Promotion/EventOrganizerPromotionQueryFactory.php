<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion;

use CultureFeed_Uitpas_Event_CultureEvent;
use CultureFeed_Uitpas_Calendar;
use CultureFeed_Uitpas_Calendar_Period;
use CultuurNet\UDB3\Clock\Clock;

class EventOrganizerPromotionQueryFactory implements PromotionQueryFactoryInterface
{
    private Clock $clock;


    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function createForEvent(
        CultureFeed_Uitpas_Event_CultureEvent $event
    ): \CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions {
        /** @var CultureFeed_Uitpas_Calendar | null $eventCalendar */
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

    private function getDateRangeFromUitpasCalendar(CultureFeed_Uitpas_Calendar $uitpasCalendar): CultureFeed_Uitpas_Calendar_Period
    {
        $dateRange = new CultureFeed_Uitpas_Calendar_Period();

        if (!empty($uitpasCalendar->periods)) {
            $firstPeriod = reset($uitpasCalendar->periods);
            $dateRange->datefrom = $firstPeriod->datefrom;

            $lastPeriod =  end($uitpasCalendar->periods);
            $dateRange->dateto = $lastPeriod->dateto;
        } elseif (!empty($uitpasCalendar->timestamps)) {
            /**
             * The custom Timestamp format for these UiTPAS calendars is a pain
             * to work with. I pick the start and end of the day to determine the
             * actual timestamps. This way events that only span one day
             * are also covered
             */
            $firstTimestamp = reset($uitpasCalendar->timestamps);
            $firstTimestampDate = new \DateTime();
            $firstTimestampDate
                ->setTimestamp($firstTimestamp->date)
                ->setTime(0, 0, 0);
            $dateRange->datefrom = $firstTimestampDate->getTimestamp();

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
