<?php

namespace CultuurNet\UDB3\Event\ReadModel\Calendar;

use CultureFeed_Cdb_Data_Calendar_Period;
use CultureFeed_Cdb_Data_Calendar_PeriodList;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\TestCase;

class CacheCalendarRepositoryTest extends TestCase
{
    /**
     * @var int
     */
    protected $eventId;

    /**
     * @var CultureFeed_Cdb_Data_Calendar_PeriodList
     */
    protected $calendar;

    public function setUp()
    {
        $this->eventId = '24b1e348-f27d-4f70-ae1a-871074267c2e';

        $this->calendar = new CultureFeed_Cdb_Data_Calendar_PeriodList();

        $period = new CultureFeed_Cdb_Data_Calendar_Period('2014-08-20', '2015-06-30');
        $this->calendar->add($period);
    }

    /**
     * @test
     */
    public function it_can_get_a_calendar_from_cache()
    {
        $serialized = serialize($this->calendar);

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('fetch')
            ->with($this->eventId)
            ->willReturn($serialized);

        $repository = new CacheCalendarRepository($cache);
        $calendarFromRepository = $repository->get($this->eventId);

        $this->assertInstanceOf(CultureFeed_Cdb_Data_Calendar_PeriodList::class, $calendarFromRepository);
    }

    /**
     * @test
     */
    public function it_handles_non_existing_calendars()
    {
        $cache = new ArrayCache();
        $repository = new CacheCalendarRepository($cache);
        $calendar = $repository->get('non-existing-id-in-cache');
        $this->assertNull($calendar);
    }

    /**
     * @test
     */
    public function it_can_save_a_calendar_to_cache()
    {
        $cache = new ArrayCache();
        $repository = new CacheCalendarRepository($cache);

        $repository->save($this->eventId, $this->calendar);

        $this->assertTrue($cache->contains($this->eventId));
        $cachedCalendar = unserialize($cache->fetch($this->eventId));
        $this->assertEquals($this->calendar, $cachedCalendar);

        $fetchedCalendar = $repository->get($this->eventId);
        $this->assertEquals($this->calendar, $fetchedCalendar);
    }
}
