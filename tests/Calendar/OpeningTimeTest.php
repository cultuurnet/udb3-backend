<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use PHPUnit\Framework\TestCase;

class OpeningTimeTest extends TestCase
{
    private Hour $hour;

    private Minute $minute;

    private OpeningTime $openingTime;

    protected function setUp(): void
    {
        $this->hour = new Hour(9);
        $this->minute = new Minute(30);
        $this->openingTime = new OpeningTime($this->hour, $this->minute);
    }

    /**
     * @test
     */
    public function it_stores_an_hour(): void
    {
        $this->assertEquals(
            $this->hour,
            $this->openingTime->getHour()
        );
    }

    /**
     * @test
     */
    public function it_store_minutes(): void
    {
        $this->assertEquals(
            $this->minute,
            $this->openingTime->getMinute()
        );
    }

    /**
     * @test
     */
    public function it_can_be_constructed_from_a_native_date_time(): void
    {
        $openingTime = OpeningTime::fromNativeDateTime(
            DateTimeFactory::fromFormat('H:i', '9:30')
        );

        $this->assertEquals($this->openingTime, $openingTime);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_from_a_native_string(): void
    {
        $openingTime = OpeningTime::fromNativeString('9:30');

        $this->assertEquals($this->openingTime, $openingTime);
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_a_native_string(): void
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
    public function it_can_compare_with_other_opening_time(): void
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
