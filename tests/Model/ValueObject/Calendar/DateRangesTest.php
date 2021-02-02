<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use const DATE_ATOM;

class DateRangesTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_never_be_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array should not be empty.');

        new SubEvents();
    }

    /**
     * @test
     */
    public function it_should_sort_the_given_date_ranges_and_return_a_start_and_end_date()
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
                    DateTimeImmutable::createFromFormat(DATE_ATOM, $from),
                    DateTimeImmutable::createFromFormat(DATE_ATOM, $to)
                ),
                new Status(StatusType::Available())
            );
        };

        $given = array_map($mapToSubEvents, $given);
        $expected = array_map($mapToSubEvents, $expected);

        $ranges = new SubEvents(...$given);

        $this->assertEquals($expected, $ranges->toArray());
        $this->assertEquals(7, $ranges->getLength());
        $this->assertEquals(
            DateTimeImmutable::createFromFormat(DATE_ATOM, '2018-01-01T00:00:00+01:00'),
            $ranges->getStartDate()
        );
        $this->assertEquals(
            DateTimeImmutable::createFromFormat(DATE_ATOM, '2018-08-31T00:00:00+01:00'),
            $ranges->getEndDate()
        );
    }
}
