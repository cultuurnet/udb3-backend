<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TimeTest extends TestCase
{
    /**
     * @test
     */
    public function it_accepts_hh_mm_format(): void
    {
        $time = new Time('15:00');

        $this->assertSame('15:00', $time->getValue());
    }

    /**
     * @test
     */
    public function it_accepts_h_mm_format_without_leading_zero(): void
    {
        $time = new Time('9:00');

        $this->assertSame('9:00', $time->getValue());
    }

    /**
     * @test
     */
    public function it_converts_time_to_minutes(): void
    {
        $time = new Time('15:30');

        $this->assertSame(930, $time->toMinutes());
    }

    /**
     * @test
     */
    public function it_converts_single_digit_hour_to_minutes(): void
    {
        $time = new Time('9:45');

        $this->assertSame(585, $time->toMinutes());
    }

    /**
     * @test
     */
    public function it_converts_midnight_to_minutes(): void
    {
        $time = new Time('0:00');

        $this->assertSame(0, $time->toMinutes());
    }

    /**
     * @test
     */
    public function it_converts_24_00_to_minutes(): void
    {
        $time = new Time('24:00');

        $this->assertSame(1440, $time->toMinutes());
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_format_single_digit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15:0" is not a valid time. Expected format is H:MM or HH:MM.');

        new Time('15:0');
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_format_missing_colon(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"1500" is not a valid time. Expected format is H:MM or HH:MM.');

        new Time('1500');
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_format_non_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"abc" is not a valid time. Expected format is H:MM or HH:MM.');

        new Time('abc');
    }

    /**
     * @test
     */
    public function it_throws_on_missing_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15" is not a valid time. Expected format is H:MM or HH:MM.');

        new Time('15');
    }

    /**
     * @test
     */
    public function it_throws_on_seconds_included(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15:00:00" is not a valid time. Expected format is H:MM or HH:MM.');

        new Time('15:00:00');
    }

    /**
     * @test
     */
    public function it_throws_when_hour_exceeds_24(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"25:00" is not a valid time. Hour must be between 0 and 24.');

        new Time('25:00');
    }

    /**
     * @test
     */
    public function it_accepts_hour_24(): void
    {
        $time = new Time('24:00');

        $this->assertSame('24:00', $time->getValue());
    }

    /**
     * @test
     */
    public function it_throws_when_hour_is_24_with_non_zero_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"24:30" is not a valid time. When hour is 24, minutes must be 0.');

        new Time('24:30');
    }

    /**
     * @test
     */
    public function it_throws_when_minutes_exceed_59(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15:60" is not a valid time. Minutes must be between 0 and 59.');

        new Time('15:60');
    }

    /**
     * @test
     */
    public function it_accepts_valid_edge_cases(): void
    {
        $this->assertEquals(new Time('0:00'), new Time('0:00'));
        $this->assertEquals(new Time('23:59'), new Time('23:59'));
        $this->assertEquals(new Time('24:00'), new Time('24:00'));
    }
}
