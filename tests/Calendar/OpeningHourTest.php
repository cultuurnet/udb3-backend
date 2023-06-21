<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use PHPUnit\Framework\TestCase;

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

    protected function setUp(): void
    {
        $this->opens = new OpeningTime(new Hour(9), new Minute(30));

        $this->closes = new OpeningTime(new Hour(17), new Minute(0));

        $this->dayOfWeekCollection = new DayOfWeekCollection(
            new DayOfWeek('monday'),
            new DayOfWeek('tuesday'),
            new DayOfWeek('wednesday'),
            new DayOfWeek('thursday'),
            new DayOfWeek('friday')
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
    public function it_stores_an_opens_time(): void
    {
        $this->assertEquals(
            $this->opens,
            $this->openingHour->getOpens()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_closes_time(): void
    {
        $this->assertEquals(
            $this->closes,
            $this->openingHour->getCloses()
        );
    }

    /**
     * @test
     */
    public function it_can_compare_on_hours(): void
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
    public function it_stores_weekdays(): void
    {
        $this->assertEquals(
            $this->dayOfWeekCollection,
            $this->openingHour->getDayOfWeekCollection()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->openingHour,
            OpeningHour::deserialize($this->openingHourAsArray)
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            $this->openingHourAsArray,
            $this->openingHour->serialize()
        );
    }
}
