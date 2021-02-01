<?php

namespace CultuurNet\UDB3\Calendar;

use PHPUnit\Framework\TestCase;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class OpeningTimeTest extends TestCase
{
    /**
     * @var Hour
     */
    private $hour;

    /**
     * @var Minute
     */
    private $minute;

    /**
     * @var OpeningTime
     */
    private $openingTime;

    protected function setUp()
    {
        $this->hour = new Hour(9);
        $this->minute = new Minute(30);
        $this->openingTime = new OpeningTime($this->hour, $this->minute);
    }

    /**
     * @test
     */
    public function it_stores_an_hour()
    {
        $this->assertEquals(
            $this->hour,
            $this->openingTime->getHour()
        );
    }

    /**
     * @test
     */
    public function it_store_minutes()
    {
        $this->assertEquals(
            $this->minute,
            $this->openingTime->getMinute()
        );
    }

    /**
     * @test
     */
    public function it_can_be_constructed_from_a_native_date_time()
    {
        $openingTime = OpeningTime::fromNativeDateTime(
            \DateTime::createFromFormat('H:i', '9:30')
        );

        $this->assertEquals($this->openingTime, $openingTime);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_from_a_native_string()
    {
        $openingTime = OpeningTime::fromNativeString('9:30');

        $this->assertEquals($this->openingTime, $openingTime);
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_a_native_string()
    {
        $this->assertEquals(
            '09:30',
            $this->openingTime->toNativeString()
        );

        $this->assertEquals(
            '09:30',
            (string) $this->openingTime
        );
    }

    /**
     * @test
     */
    public function it_can_compare_with_other_opening_time()
    {
        $sameOpeningTime = new OpeningTime(new Hour(9), new Minute(30));
        $differentOpeningTime = new OpeningTime(new Hour(10), new Minute(30));

        $this->assertTrue(
            $this->openingTime->sameValueAs($sameOpeningTime)
        );
        $this->assertFalse(
            $this->openingTime->sameValueAs($differentOpeningTime)
        );
    }
}
