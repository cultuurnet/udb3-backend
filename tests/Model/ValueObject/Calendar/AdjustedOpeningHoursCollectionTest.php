<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AdjustedOpeningHoursCollectionTest extends TestCase
{
    private OpeningHours $openingHours;

    protected function setUp(): void
    {
        $this->openingHours = new OpeningHours(
            new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00'))
        );
    }

    /**
     * @test
     */
    public function it_creates_an_empty_collection(): void
    {
        $collection = new AdjustedOpeningHoursCollection();

        $this->assertTrue($collection->isEmpty());
        $this->assertEquals(0, $collection->count());
        $this->assertEquals([], $collection->toArray());
    }

    /**
     * @test
     */
    public function it_creates_a_collection_with_a_single_entry(): void
    {
        $entry = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-25'),
            new DateTimeImmutable('2026-12-25'),
            $this->openingHours
        );

        $collection = new AdjustedOpeningHoursCollection($entry);

        $this->assertFalse($collection->isEmpty());
        $this->assertEquals(1, $collection->count());

        $array = $collection->toArray();
        $this->assertCount(1, $array);
        $this->assertSame($entry, $array[0]);
    }

    /**
     * @test
     */
    public function it_sorts_entries_by_start_date_ascending(): void
    {
        $entry1 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-25'),
            new DateTimeImmutable('2026-12-25'),
            $this->openingHours
        );
        $entry2 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-01'),
            $this->openingHours
        );
        $entry3 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-07-21'),
            new DateTimeImmutable('2026-07-21'),
            $this->openingHours
        );

        $collection = new AdjustedOpeningHoursCollection($entry1, $entry2, $entry3);

        $array = $collection->toArray();

        $this->assertEquals(new DateTimeImmutable('2026-01-01'), $array[0]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2026-07-21'), $array[1]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2026-12-25'), $array[2]->getStartDate());
    }

    /**
     * @test
     */
    public function it_maintains_order_when_start_dates_are_equal(): void
    {
        $entry1 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-25T00:00:00'),
            new DateTimeImmutable('2026-12-31T00:00:00'),
            $this->openingHours
        );
        $entry2 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-25T10:00:00'),
            new DateTimeImmutable('2026-12-26T00:00:00'),
            $this->openingHours
        );

        $collection = new AdjustedOpeningHoursCollection($entry1, $entry2);

        $array = $collection->toArray();

        $this->assertEquals(new DateTimeImmutable('2026-12-25T00:00:00'), $array[0]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2026-12-31T00:00:00'), $array[0]->getEndDate());
    }

}
