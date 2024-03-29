<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use PHPUnit\Framework\TestCase;

class DayOfWeekCollectionTest extends TestCase
{
    private DayOfWeekCollection $dayOfWeekCollection;

    protected function setUp(): void
    {
        $this->dayOfWeekCollection = new DayOfWeekCollection(
            DayOfWeek::WEDNESDAY()
        );
    }

    /**
     * @test
     */
    public function it_gets_constructed_as_an_empty_collection(): void
    {
        $daysOfWeekCollection = new DayOfWeekCollection();

        $this->assertEmpty($daysOfWeekCollection->getDaysOfWeek());
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_a_single_day_of_the_week(): void
    {
        $daysOfWeekCollection = new DayOfWeekCollection(
            DayOfWeek::WEDNESDAY()
        );

        $this->assertEquals(
            [
                DayOfWeek::WEDNESDAY(),
            ],
            $daysOfWeekCollection->getDaysOfWeek()
        );
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_a_multiple_day_of_the_week(): void
    {
        $daysOfWeekCollection = new DayOfWeekCollection(
            DayOfWeek::WEDNESDAY(),
            DayOfWeek::FRIDAY()
        );

        $this->assertEquals(
            [
                DayOfWeek::WEDNESDAY(),
                DayOfWeek::FRIDAY(),
            ],
            $daysOfWeekCollection->getDaysOfWeek()
        );
    }

    /**
     * @test
     */
    public function it_allows_adding_more_days_of_the_week(): void
    {
        $this->dayOfWeekCollection->addDayOfWeek(DayOfWeek::FRIDAY());

        $this->assertEquals(
            [
                DayOfWeek::WEDNESDAY(),
                DayOfWeek::FRIDAY(),
            ],
            $this->dayOfWeekCollection->getDaysOfWeek()
        );
    }

    /**
     * @test
     */
    public function it_only_adds_unique_days(): void
    {
        $this->dayOfWeekCollection->addDayOfWeek(DayOfWeek::FRIDAY());
        $this->dayOfWeekCollection->addDayOfWeek(DayOfWeek::FRIDAY());

        $this->assertEquals(
            [
                DayOfWeek::WEDNESDAY(),
                DayOfWeek::FRIDAY(),
            ],
            $this->dayOfWeekCollection->getDaysOfWeek()
        );
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $this->dayOfWeekCollection->addDayOfWeek(DayOfWeek::MONDAY());

        $this->assertEquals(
            [
                'wednesday',
                'monday',
            ],
            $this->dayOfWeekCollection->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $this->dayOfWeekCollection->addDayOfWeek(DayOfWeek::MONDAY());

        $this->assertEquals(
            DayOfWeekCollection::deserialize(
                [
                    'wednesday',
                    'monday',
                ]
            ),
            $this->dayOfWeekCollection
        );
    }

    /**
     * @test
     */
    public function it_allows_built_in_serialize(): void
    {
        $serialized = serialize($this->dayOfWeekCollection);

        $this->assertNotEmpty($serialized);
    }
}
