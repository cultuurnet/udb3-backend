<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AdjustedDaysTest extends TestCase
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
        $collection = new AdjustedDays();

        $this->assertTrue($collection->isEmpty());
        $this->assertEquals(0, $collection->count());
        $this->assertEquals([], $collection->toArray());
    }

    /**
     * @test
     */
    public function it_creates_a_collection_with_a_single_entry(): void
    {
        $entry = new AdjustedDay(
            new DateTimeImmutable('2026-12-25'),
            new DateTimeImmutable('2026-12-25'),
            $this->openingHours
        );

        $collection = new AdjustedDays($entry);

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
        $entry1 = new AdjustedDay(
            new DateTimeImmutable('2026-12-25'),
            new DateTimeImmutable('2026-12-25'),
            $this->openingHours
        );
        $entry2 = new AdjustedDay(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-01'),
            $this->openingHours
        );
        $entry3 = new AdjustedDay(
            new DateTimeImmutable('2026-07-21'),
            new DateTimeImmutable('2026-07-21'),
            $this->openingHours
        );

        $collection = new AdjustedDays($entry1, $entry2, $entry3);

        $array = $collection->toArray();

        $this->assertEquals(new DateTimeImmutable('2026-01-01'), $array[0]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2026-07-21'), $array[1]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2026-12-25'), $array[2]->getStartDate());
    }

    /**
     * @test
     */
    public function it_throws_when_two_entries_share_the_same_start_day(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OpeningHoursAdjustedPeriods cannot contain two entries with the same start date.');

        new AdjustedDays(
            new AdjustedDay(
                new DateTimeImmutable('2026-12-25T00:00:00'),
                new DateTimeImmutable('2026-12-31T00:00:00'),
                $this->openingHours
            ),
            new AdjustedDay(
                new DateTimeImmutable('2026-12-25T10:00:00'),
                new DateTimeImmutable('2026-12-26T00:00:00'),
                $this->openingHours
            )
        );
    }

    /**
     * @test
     */
    public function it_removes_the_childcare_time_range_from_every_adjusted_day(): void
    {
        $openingHoursWithChildcare = new OpeningHours(
            (new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00')))
                ->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('08:00'), Time::fromString('18:00')))
        );

        $collection = new AdjustedDays(
            new AdjustedDay(
                new DateTimeImmutable('2026-12-25'),
                new DateTimeImmutable('2026-12-25'),
                $openingHoursWithChildcare
            ),
            new AdjustedDay(
                new DateTimeImmutable('2026-12-26'),
                new DateTimeImmutable('2026-12-26'),
                $openingHoursWithChildcare
            )
        );

        foreach ($collection->withoutChildcare()->toArray() as $adjustedDay) {
            foreach ($adjustedDay->getOpeningHours()->toArray() as $openingHour) {
                $this->assertNull($openingHour->getChildcareTimeRange());
            }
        }
    }

    /**
     * @test
     */
    public function it_keeps_the_dates_and_description_when_removing_the_childcare_time_range(): void
    {
        $description = new TranslatedAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstvakantie')
        );

        $collection = new AdjustedDays(
            new AdjustedDay(
                new DateTimeImmutable('2026-12-25'),
                new DateTimeImmutable('2026-12-31'),
                $this->openingHours,
                $description
            )
        );

        $adjustedDay = $collection->withoutChildcare()->toArray()[0];

        $this->assertEquals(new DateTimeImmutable('2026-12-25'), $adjustedDay->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2026-12-31'), $adjustedDay->getEndDate());
        $this->assertEquals($description, $adjustedDay->getDescription());
    }
}
