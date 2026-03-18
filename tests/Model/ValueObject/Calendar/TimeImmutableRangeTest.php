<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TimeImmutableRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_accepts_null_for_both_start_and_end(): void
    {
        $range = new TimeImmutableRange();

        $this->assertNull($range->getStart());
        $this->assertNull($range->getEnd());
    }

    /**
     * @test
     */
    public function it_accepts_hh_mm_format(): void
    {
        $start = Time::fromString('15:00');
        $end = Time::fromString('23:00');
        $range = new TimeImmutableRange($start, $end);

        $this->assertEquals($start, $range->getStart());
        $this->assertEquals($end, $range->getEnd());
        $this->assertSame('15:00', $range->getStart()->getValue());
        $this->assertSame('23:00', $range->getEnd()->getValue());
    }

    /**
     * @test
     */
    public function it_accepts_h_mm_format_without_leading_zero(): void
    {
        $start = Time::fromString('9:00');
        $end = Time::fromString('9:30');
        $range = new TimeImmutableRange($start, $end);

        // Note: OpeningHours\Time normalizes to HH:MM format
        $this->assertSame('09:00', $range->getStart()->getValue());
        $this->assertSame('09:30', $range->getEnd()->getValue());
    }

    /**
     * @test
     */
    public function it_accepts_only_start(): void
    {
        $start = Time::fromString('15:00');
        $range = new TimeImmutableRange($start);

        $this->assertEquals($start, $range->getStart());
        $this->assertNull($range->getEnd());
    }

    /**
     * @test
     */
    public function it_accepts_only_end(): void
    {
        $end = Time::fromString('23:00');
        $range = new TimeImmutableRange(null, $end);

        $this->assertNull($range->getStart());
        $this->assertEquals($end, $range->getEnd());
    }

    /**
     * @test
     */
    public function it_throws_when_start_is_after_end(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"23:00" must be before "15:00".');

        new TimeImmutableRange(Time::fromString('23:00'), Time::fromString('15:00'));
    }

    /**
     * @test
     */
    public function it_throws_when_start_equals_end(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15:00" must be before "15:00".');

        new TimeImmutableRange(Time::fromString('15:00'), Time::fromString('15:00'));
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_start_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Time::fromString('15:0');
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_end_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Time::fromString('abc');
    }

    /**
     * @test
     */
    public function it_throws_on_missing_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Time::fromString('15');
    }

    /**
     * @test
     */
    public function it_throws_on_seconds_included(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Time::fromString('15:00:00');
    }
}
