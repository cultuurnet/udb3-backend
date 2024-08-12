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
                new BookingAvailability(BookingAvailabilityType::Available())
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
}
