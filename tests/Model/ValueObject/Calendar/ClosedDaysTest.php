<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ClosedDaysTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_empty_closed_days_collection(): void
    {
        $closedDays = new ClosedDays();

        $this->assertTrue($closedDays->isEmpty());
        $this->assertEquals(0, $closedDays->count());
        $this->assertEquals([], $closedDays->toArray());
    }

    /**
     * @test
     */
    public function it_creates_a_collection_with_single_closed_day(): void
    {
        $closedDay = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-25')
        );

        $closedDays = new ClosedDays($closedDay);

        $this->assertFalse($closedDays->isEmpty());
        $this->assertEquals(1, $closedDays->count());

        $array = $closedDays->toArray();
        $this->assertCount(1, $array);
        $this->assertSame($closedDay, $array[0]);
    }

    /**
     * @test
     */
    public function it_creates_a_collection_with_multiple_closed_days(): void
    {
        $closedDay1 = new ClosedDay(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-01')
        );
        $closedDay2 = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-26')
        );

        $closedDays = new ClosedDays($closedDay1, $closedDay2);

        $this->assertFalse($closedDays->isEmpty());
        $this->assertEquals(2, $closedDays->count());

        $array = $closedDays->toArray();
        $this->assertCount(2, $array);
    }

    /**
     * @test
     */
    public function it_sorts_closed_days_by_start_date_ascending(): void
    {
        $closedDay1 = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-25')
        );
        $closedDay2 = new ClosedDay(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-01')
        );
        $closedDay3 = new ClosedDay(
            new DateTimeImmutable('2024-07-21'),
            new DateTimeImmutable('2024-07-21')
        );

        // Create in non-sorted order
        $closedDays = new ClosedDays($closedDay1, $closedDay2, $closedDay3);

        $array = $closedDays->toArray();

        // Should be sorted by startDate ascending
        $this->assertEquals(
            new DateTimeImmutable('2024-01-01'),
            $array[0]->getStartDate()
        );
        $this->assertEquals(
            new DateTimeImmutable('2024-07-21'),
            $array[1]->getStartDate()
        );
        $this->assertEquals(
            new DateTimeImmutable('2024-12-25'),
            $array[2]->getStartDate()
        );
    }

    /**
     * @test
     */
    public function it_throws_when_two_entries_share_the_same_start_day(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ClosedDays cannot contain two entries with the same start date.');

        new ClosedDays(
            new ClosedDay(
                new DateTimeImmutable('2024-12-25T00:00:00'),
                new DateTimeImmutable('2024-12-25T23:59:59')
            ),
            new ClosedDay(
                new DateTimeImmutable('2024-12-25T10:00:00'),
                new DateTimeImmutable('2024-12-25T11:00:00')
            )
        );
    }

    /**
     * @test
     */
    public function it_can_be_iterated_when_converted_to_array(): void
    {
        $closedDay1 = new ClosedDay(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-01')
        );
        $closedDay2 = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-25')
        );

        $closedDays = new ClosedDays($closedDay1, $closedDay2);

        $count = 0;
        foreach ($closedDays->toArray() as $closedDay) {
            $this->assertInstanceOf(ClosedDay::class, $closedDay);
            $count++;
        }

        $this->assertEquals(2, $count);
    }
}
