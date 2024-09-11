<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\DateTimeFactory;
use PHPUnit\Framework\TestCase;

class CalendarUpdatedTest extends TestCase
{
    private string $placeId;

    private Calendar $calendar;

    private array $calendarUpdatedAsArray;

    private CalendarUpdated $calendarUpdated;

    protected function setUp(): void
    {
        $this->placeId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $this->calendar = new Calendar(
            CalendarType::PERIODIC(),
            DateTimeFactory::fromAtom('2020-01-26T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-01-27T12:12:12+01:00')
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
                'bookingAvailability' => [
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
    public function it_stores_an_event_id(): void
    {
        $this->assertEquals($this->placeId, $this->calendarUpdated->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_a_calendar(): void
    {
        $this->assertEquals($this->calendar, $this->calendarUpdated->getCalendar());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            $this->calendarUpdatedAsArray,
            $this->calendarUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->calendarUpdated,
            CalendarUpdated::deserialize($this->calendarUpdatedAsArray)
        );
    }
}
