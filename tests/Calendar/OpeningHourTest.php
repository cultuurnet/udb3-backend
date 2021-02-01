<?php

namespace CultuurNet\UDB3\Calendar;

use PHPUnit\Framework\TestCase;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class OpeningHourTest extends TestCase
{
    /**
     * @var OpeningTime
     */
    private $opens;

    /**
     * @var OpeningTime
     */
    private $closes;

    /**
     * @var DayOfWeekCollection
     */
    private $dayOfWeekCollection;

    /**
     * @var array
     */
    private $openingHourAsArray;

    /**
     * @var OpeningHour
     */
    private $openingHour;

    protected function setUp()
    {
        $this->opens = new OpeningTime(new Hour(9), new Minute(30));

        $this->closes = new OpeningTime(new Hour(17), new Minute(0));

        $this->dayOfWeekCollection = new DayOfWeekCollection(
            DayOfWeek::fromNative('monday'),
            DayOfWeek::fromNative('tuesday'),
            DayOfWeek::fromNative('wednesday'),
            DayOfWeek::fromNative('thursday'),
            DayOfWeek::fromNative('friday')
        );

        $this->openingHourAsArray = [
            'opens' => '09:30',
            'closes' => '17:00',
            'dayOfWeek' => [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
            ],
        ];

        $this->openingHour = new OpeningHour(
            $this->opens,
            $this->closes,
            $this->dayOfWeekCollection
        );
    }

    /**
     * @test
     */
    public function it_stores_an_opens_time()
    {
        $this->assertEquals(
            $this->opens,
            $this->openingHour->getOpens()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_closes_time()
    {
        $this->assertEquals(
            $this->closes,
            $this->openingHour->getCloses()
        );
    }

    /**
     * @test
     */
    public function it_can_compare_on_hours()
    {
        $sameOpeningHour = new OpeningHour(
            new OpeningTime(new Hour(9), new Minute(30)),
            new OpeningTime(new Hour(17), new Minute(0)),
            new DayOfWeekCollection(DayOfWeek::MONDAY())
        );

        $differentOpeningHour = new OpeningHour(
            new OpeningTime(new Hour(10), new Minute(30)),
            new OpeningTime(new Hour(17), new Minute(0)),
            new DayOfWeekCollection(DayOfWeek::MONDAY())
        );

        $this->assertTrue(
            $this->openingHour->hasEqualHours($sameOpeningHour)
        );
        $this->assertFalse(
            $this->openingHour->hasEqualHours($differentOpeningHour)
        );
    }

    /**
     * @test
     */
    public function it_stores_weekdays()
    {
        $this->assertEquals(
            $this->dayOfWeekCollection,
            $this->openingHour->getDayOfWeekCollection()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $this->assertEquals(
            $this->openingHour,
            OpeningHour::deserialize($this->openingHourAsArray)
        );
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $this->assertEquals(
            $this->openingHourAsArray,
            $this->openingHour->serialize()
        );
    }
}
