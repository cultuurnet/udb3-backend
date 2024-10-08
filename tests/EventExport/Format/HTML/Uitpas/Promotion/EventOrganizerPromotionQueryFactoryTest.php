<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion;

use CultureFeed_Uitpas_Calendar;
use CultureFeed_Uitpas_Calendar_Timestamp;
use CultureFeed_Uitpas_Event_CultureEvent;
use CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Clock\FrozenClock;
use PHPUnit\Framework\TestCase;

class EventOrganizerPromotionQueryFactoryTest extends TestCase
{
    protected EventOrganizerPromotionQueryFactory $queryFactory;

    protected int $unixTime = 435052800;

    public function setUp(): void
    {
        $this->queryFactory = new EventOrganizerPromotionQueryFactory(
            new FrozenClock(
                DateTimeFactory::fromFormat(
                    'U',
                    (string) $this->unixTime,
                    new \DateTimeZone('Europe/Brussels')
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_creates_query_options_with_cashing_period_that_matches_event(): void
    {
        $eventCalendar = new CultureFeed_Uitpas_Calendar();
        $today = new \DateTimeImmutable();
        $tomorrow = $today->modify('+1 day');

        $timestampToday = new CultureFeed_Uitpas_Calendar_Timestamp();
        $timestampToday->date = $today->getTimestamp();

        $timestampTomorrow = new CultureFeed_Uitpas_Calendar_Timestamp();
        $timestampTomorrow->date = $tomorrow->getTimestamp();

        $eventCalendar->addTimestamp($timestampToday);
        $eventCalendar->addTimestamp($timestampTomorrow);

        $event = new CultureFeed_Uitpas_Event_CultureEvent();
        $event->organiserId = 'xyz';
        $event->calendar = $eventCalendar;

        $query = $this->queryFactory->createForEvent($event);

        $expectedFromDate = $today->setTime(0, 0, 0)->getTimestamp();
        $expectedToDate = $tomorrow->setTime(24, 59, 59)->getTimestamp();

        $expectedQuery = $this->createBaseQuery();
        $expectedQuery->balieConsumerKey = $event->organiserId;
        $expectedQuery->cashingPeriodBegin = $expectedFromDate;
        $expectedQuery->cashingPeriodEnd = $expectedToDate;

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_handles_a_calendar_with_periods(): void
    {
        $eventCalendar = new CultureFeed_Uitpas_Calendar();

        $period = new \CultureFeed_Uitpas_Calendar_Period();
        $period->datefrom = 1420070400;
        $period->dateto = 1422748800;
        $eventCalendar->addPeriod($period);

        $secondPeriod = new \CultureFeed_Uitpas_Calendar_Period();
        $secondPeriod->datefrom = 1425168000;
        $secondPeriod->dateto = 1427846400;

        $eventCalendar->addPeriod($secondPeriod);

        $event = new CultureFeed_Uitpas_Event_CultureEvent();
        $event->organiserId = 'xyz';
        $event->calendar = $eventCalendar;

        $query = $this->queryFactory->createForEvent($event);

        $expectedFromDate = $period->datefrom;
        $expectedToDate = $secondPeriod->dateto;

        $expectedQuery = $this->createBaseQuery();
        $expectedQuery->balieConsumerKey = $event->organiserId;
        $expectedQuery->cashingPeriodBegin = $expectedFromDate;
        $expectedQuery->cashingPeriodEnd = $expectedToDate;

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_uses_the_system_time_if_other_event_calendar_than_periods_or_timestamps(): void
    {
        $eventCalendar = new CultureFeed_Uitpas_Calendar();

        $event = new CultureFeed_Uitpas_Event_CultureEvent();
        $event->organiserId = 'xyz';
        $event->calendar = $eventCalendar;

        $query = $this->queryFactory->createForEvent($event);

        $expectedQuery = $this->createBaseQuery();
        $expectedQuery->balieConsumerKey = $event->organiserId;
        $expectedQuery->cashingPeriodBegin = $this->unixTime;

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * Creates the base for the query, with all necessary properties set that
     * are independent from the cultural event passed to createForEvent().
     */
    private function createBaseQuery(): CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions
    {
        $expectedQueryOptions = new CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions();
        $expectedQueryOptions->max = 2;
        $expectedQueryOptions->unexpired = true;

        return $expectedQueryOptions;
    }
}
