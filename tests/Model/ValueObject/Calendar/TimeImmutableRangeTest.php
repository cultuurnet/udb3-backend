<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

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
        $range = new TimeImmutableRange('15:00', '23:00');

        $this->assertSame('15:00', $range->getStart());
        $this->assertSame('23:00', $range->getEnd());
    }

    /**
     * @test
     */
    public function it_accepts_h_mm_format_without_leading_zero(): void
    {
        $range = new TimeImmutableRange('9:00', '9:30');

        $this->assertSame('9:00', $range->getStart());
        $this->assertSame('9:30', $range->getEnd());
    }

    /**
     * @test
     */
    public function it_accepts_only_start(): void
    {
        $range = new TimeImmutableRange('15:00');

        $this->assertSame('15:00', $range->getStart());
        $this->assertNull($range->getEnd());
    }

    /**
     * @test
     */
    public function it_accepts_only_end(): void
    {
        $range = new TimeImmutableRange(null, '23:00');

        $this->assertNull($range->getStart());
        $this->assertSame('23:00', $range->getEnd());
    }

    /**
     * @test
     */
    public function it_throws_when_start_is_after_end(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"23:00" must be before "15:00".');

        new TimeImmutableRange('23:00', '15:00');
    }

    /**
     * @test
     */
    public function it_throws_when_start_equals_end(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15:00" must be before "15:00".');

        new TimeImmutableRange('15:00', '15:00');
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_start_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15:0" is not a valid time. Expected format is H:MM or HH:MM.');

        new TimeImmutableRange('15:0');
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_end_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"abc" is not a valid time. Expected format is H:MM or HH:MM.');

        new TimeImmutableRange(null, 'abc');
    }

    /**
     * @test
     */
    public function it_throws_on_missing_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeImmutableRange('15');
    }

    /**
     * @test
     */
    public function it_throws_on_seconds_included(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeImmutableRange('15:00:00');
    }

    /**
     * @test
     */
    public function it_throws_when_hour_exceeds_24(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"25:00" is not a valid time. Hour must be between 0 and 24.');

        new TimeImmutableRange('25:00');
    }

    /**
     * @test
     */
    public function it_accepts_hour_24(): void
    {
        $range = new TimeImmutableRange('24:00');

        $this->assertSame('24:00', $range->getStart());
    }

    /**
     * @test
     */
    public function it_throws_when_minutes_exceed_59(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"15:60" is not a valid time. Minutes must be between 0 and 59.');

        new TimeImmutableRange('15:60');
    }

    /**
     * @test
     */
    public function it_throws_on_end_with_invalid_hour(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"25:00" is not a valid time. Hour must be between 0 and 24.');

        new TimeImmutableRange(null, '25:00');
    }

    /**
     * @test
     */
    public function it_throws_on_end_with_invalid_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"23:60" is not a valid time. Minutes must be between 0 and 59.');

        new TimeImmutableRange(null, '23:60');
    }
}
