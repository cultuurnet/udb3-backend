<?php

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use PHPUnit\Framework\TestCase;

class CalendarUpdatedTest extends TestCase
{
    /**
     * @var string
     */
    private $placeId;

    /**
     * @var Calendar
     */
    private $calendar;

    /**
     * @var array
     */
    private $calendarUpdatedAsArray;

    /**
     * @var CalendarUpdated
     */
    private $calendarUpdated;

    protected function setUp()
    {
        $this->placeId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $this->calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $this->calendarUpdatedAsArray = [
            'item_id' => '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a',
            'calendar' => [
                'type' => 'periodic',
                'startDate' => '2020-01-26T11:11:11+01:00',
                'endDate' => '2020-01-27T12:12:12+01:00',
                'status' => [
                    'type' => 'Available',
                ],
            ],
        ];

        $this->calendarUpdated = new CalendarUpdated(
            $this->placeId,
            $this->calendar
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id()
    {
        $this->assertEquals($this->placeId, $this->calendarUpdated->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_a_calendar()
    {
        $this->assertEquals($this->calendar, $this->calendarUpdated->getCalendar());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $this->assertEquals(
            $this->calendarUpdatedAsArray,
            $this->calendarUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $this->assertEquals(
            $this->calendarUpdated,
            CalendarUpdated::deserialize($this->calendarUpdatedAsArray)
        );
    }
}
