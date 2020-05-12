<?php

namespace CultuurNet\UDB3\Event\ReadModel\Calendar;

use CultureFeed_Cdb_Data_Calendar as Calendar;
use CultureFeed_Cdb_Data_Calendar_OpeningTime as OpeningTime;
use CultureFeed_Cdb_Data_Calendar_Period as Period;
use CultureFeed_Cdb_Data_Calendar_PeriodList as PeriodList;
use CultureFeed_Cdb_Data_Calendar_SchemeDay as SchemeDay;
use CultureFeed_Cdb_Data_Calendar_Timestamp as Timestamp;
use CultureFeed_Cdb_Data_Calendar_TimestampList as TimestampList;
use CultureFeed_Cdb_Data_Calendar_Weekscheme as WeekScheme;
use CultuurNet\UDB3\Event\CdbXMLEventFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventCalendarProjectorTest extends TestCase
{
    const CDBXML_NAMESPACE_33 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';
    const CDBXML_NAMESPACE_32 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

    /**
     * @var CalendarRepositoryInterface|MockObject
     */
    protected $repository;

    /**
     * @var EventCalendarProjector
     */
    protected $projector;

    /**
     * @var CdbXMLEventFactory
     */
    protected $cdbXMLEventFactory;

    public function setUp()
    {
        $this->cdbXMLEventFactory = new CdbXMLEventFactory();

        $this->repository = $this->createMock(CalendarRepositoryInterface::class);
        $this->projector = new EventCalendarProjector($this->repository);
    }

    /**
     * @test
     */
    public function it_saves_the_calendar_periods_from_events_imported_from_udb2()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2('samples/event_with_calendar_periods.cdbxml.xml');
        $this->repositoryExpectsCalendarToBeSaved('someId', $this->getPeriodList());
        $this->projector->applyEventImportedFromUDB2($event);
    }

    /**
     * @test
     */
    public function it_saves_the_calendar_periods_from_events_updated_from_udb2()
    {
        $event = $this->cdbXMLEventFactory->eventUpdatedFromUDB2('samples/event_with_calendar_periods.cdbxml.xml');
        $this->repositoryExpectsCalendarToBeSaved('someId', $this->getPeriodList());
        $this->projector->applyEventUpdatedFromUDB2($event);
    }

    /**
     * @test
     */
    public function it_saves_the_calendar_timestamps_from_events_imported_from_udb2()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2('samples/event_with_calendar_timestamps.cdbxml.xml');
        $this->repositoryExpectsCalendarToBeSaved('someId', $this->getTimestampList());
        $this->projector->applyEventImportedFromUDB2($event);
    }

    /**
     * @test
     */
    public function it_saves_the_calendar_timestamps_from_events_updated_from_udb2()
    {
        $event = $this->cdbXMLEventFactory->eventUpdatedFromUDB2('samples/event_with_calendar_timestamps.cdbxml.xml');
        $this->repositoryExpectsCalendarToBeSaved('someId', $this->getTimestampList());
        $this->projector->applyEventUpdatedFromUDB2($event);
    }

    /**
     * @return PeriodList
     */
    private function getPeriodList()
    {
        $periodList = new PeriodList();

        $period = new Period('2014-11-01', '2014-11-09');
        $weekScheme = new WeekScheme();

        $closedDays = ['monday', 'tuesday', 'wednesday'];
        foreach ($closedDays as $closedDay) {
            $day = new SchemeDay($closedDay, SchemeDay::SCHEMEDAY_OPEN_TYPE_CLOSED);
            $weekScheme->setDay($closedDay, $day);
        }

        $openDays = ['thursday', 'friday', 'saturday', 'sunday'];
        foreach ($openDays as $openDay) {
            $day = new SchemeDay($openDay, SchemeDay::SCHEMEDAY_OPEN_TYPE_OPEN);
            $openingTime = new OpeningTime('13:30:00', '18:00:00');
            $day->addOpeningTime($openingTime);
            $weekScheme->setDay($openDay, $day);
        }

        $period->setWeekScheme($weekScheme);
        $periodList->add($period);

        return $periodList;
    }

    /**
     * @return TimestampList
     */
    private function getTimestampList()
    {
        $timestampList = new TimestampList();

        $timestamp = new Timestamp('2014-11-21', '20:00:00');
        $timestampList->add($timestamp);

        return $timestampList;
    }

    /**
     * @param string $id
     * @param Calendar $calendar
     */
    private function repositoryExpectsCalendarToBeSaved($id, Calendar $calendar)
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($id, $calendar);
    }
}
