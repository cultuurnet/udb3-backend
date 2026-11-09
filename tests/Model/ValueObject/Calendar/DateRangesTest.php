<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use PHPUnit\Framework\TestCase;

class DateRangesTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_never_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array should not be empty.');

        new SubEvents();
    }

    /**
     * @test
     */
    public function it_should_sort_the_given_date_ranges_and_return_a_start_and_end_date(): void
    {
        $given = [
            ['2018-07-01T00:00:00+01:00', '2018-08-31T00:00:00+01:00'],
            ['2018-07-01T00:00:00+01:00', '2018-08-30T00:00:00+01:00'],
            ['2018-05-30T00:00:00+01:00', '2018-09-01T00:00:00+01:00'],
            ['2018-01-01T00:00:00+01:00', '2018-01-01T00:00:00+01:00'],
            ['2018-05-30T00:00:00+01:00', '2018-08-30T00:00:00+01:00'],
            ['2018-01-01T00:00:00+01:00', '2018-12-01T00:00:00+01:00'],
            ['2018-07-01T00:00:00+01:00', '2018-08-31T00:00:00+01:00'],
        ];

        $expected = [
            ['2018-01-01T00:00:00+01:00', '2018-01-01T00:00:00+01:00'],
            ['2018-01-01T00:00:00+01:00', '2018-12-01T00:00:00+01:00'],
            ['2018-05-30T00:00:00+01:00', '2018-08-30T00:00:00+01:00'],
            ['2018-05-30T00:00:00+01:00', '2018-09-01T00:00:00+01:00'],
            ['2018-07-01T00:00:00+01:00', '2018-08-30T00:00:00+01:00'],
            ['2018-07-01T00:00:00+01:00', '2018-08-31T00:00:00+01:00'],
            ['2018-07-01T00:00:00+01:00', '2018-08-31T00:00:00+01:00'],
        ];

        $mapToSubEvents = function (array $range) {
            $from = $range[0];
            $to = $range[1];

            return new SubEvent(
                new DateRange(
                    DateTimeFactory::fromAtom($from),
                    DateTimeFactory::fromAtom($to)
                ),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available()),
            );
        };

        $given = array_map($mapToSubEvents, $given);
        $expected = array_map($mapToSubEvents, $expected);

        $ranges = new SubEvents(...$given);

        $this->assertEquals($expected, $ranges->toArray());
        $this->assertEquals(7, $ranges->getLength());
        $this->assertEquals(
            DateTimeFactory::fromAtom('2018-01-01T00:00:00+01:00'),
            $ranges->getStartDate()
        );
        $this->assertEquals(
            DateTimeFactory::fromAtom('2018-08-31T00:00:00+01:00'),
            $ranges->getEndDate()
        );
    }

    /**
     * @test
     */
    public function it_keeps_the_capacity_of_the_current_sub_event_with_the_same_dates(): void
    {
        $sent = new SubEvents(
            $this->getSubEvent('2026-11-05'),
            $this->getSubEvent('2026-11-07'),
        );
        $current = [
            $this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(30, 12)),
            $this->getSubEvent('2026-11-06')->withCapacity(Capacity::unlimited(140)),
        ];

        $result = $sent->withCapacitiesFrom(...$current)->toArray();

        $this->assertEquals(Capacity::limited(30, 12), $result[0]->getCapacity());
        $this->assertNull($result[1]->getCapacity());
    }

    /**
     * @test
     * @dataProvider differentDatesProvider
     */
    public function it_does_not_keep_the_capacity_when_the_dates_differ(SubEvent $sentSubEvent): void
    {
        $sent = new SubEvents($sentSubEvent);
        $current = $this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(30, 12));

        $result = $sent->withCapacitiesFrom($current)->toArray();

        $this->assertNull($result[0]->getCapacity());
    }

    public function differentDatesProvider(): array
    {
        return [
            'different end date' => [$this->getSubEvent('2026-11-05', '10:00', '14:00')],
            'different start date' => [$this->getSubEvent('2026-11-05', '09:00', '12:00')],
            'different start and end date' => [$this->getSubEvent('2026-11-06')],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_keep_the_capacity_when_several_current_sub_events_have_the_same_dates(): void
    {
        $sent = new SubEvents($this->getSubEvent('2026-11-05'));
        $current = [
            $this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(10)),
            $this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(20)),
        ];

        $result = $sent->withCapacitiesFrom(...$current)->toArray();

        $this->assertNull($result[0]->getCapacity());
    }

    /**
     * @test
     */
    public function it_uses_the_sent_capacity_over_the_current_one(): void
    {
        $sent = new SubEvents($this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(40)));
        $current = $this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(30, 12));

        $result = $sent->withCapacitiesFrom($current)->toArray();

        $this->assertEquals(Capacity::limited(40), $result[0]->getCapacity());
    }

    /**
     * @test
     */
    public function it_keeps_the_current_capacity_when_no_capacity_is_sent(): void
    {
        $sent = new SubEvents($this->getSubEvent('2026-11-05'));
        $current = $this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(30, 12));

        $result = $sent->withCapacitiesFrom($current)->toArray();

        $this->assertEquals(Capacity::limited(30, 12), $result[0]->getCapacity());
    }

    /**
     * @test
     */
    public function it_clears_the_current_capacity_when_an_unknown_capacity_is_sent(): void
    {
        $sent = new SubEvents($this->getSubEvent('2026-11-05')->withCapacity(Capacity::unknown()));
        $current = $this->getSubEvent('2026-11-05')->withCapacity(Capacity::limited(30, 12));

        $result = $sent->withCapacitiesFrom($current)->toArray();

        $this->assertNull($result[0]->getCapacity());
    }

    private function getSubEvent(string $date, string $startTime = '10:00', string $endTime = '12:00'): SubEvent
    {
        return SubEvent::createAvailable(
            new DateRange(
                new \DateTimeImmutable($date . 'T' . $startTime . ':00+01:00'),
                new \DateTimeImmutable($date . 'T' . $endTime . ':00+01:00')
            )
        );
    }
}
