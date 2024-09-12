<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class DateTimeFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider validISO8601DataProvider
     */
    public function it_creates_a_date_time_object_from_a_valid_iso_8601_string(string $given, string $expectedAsRFC3339InBrussels): void
    {
        $object = DateTimeFactory::fromISO8601($given)->setTimezone(new DateTimeZone('Europe/Brussels'));
        $asRFC3339 = $object->format(DateTimeImmutable::RFC3339);
        $this->assertEquals($expectedAsRFC3339InBrussels, $asRFC3339);
    }

    public function validISO8601DataProvider(): array
    {
        return [
            'utc' => [
                'given' => '2022-02-28T13:23:47Z',
                'expectedAsRFC3339InBrussels' => '2022-02-28T14:23:47+01:00',
            ],
            'offset' => [
                'given' => '2022-02-28T13:23:47+01:30',
                'expectedAsRFC3339InBrussels' => '2022-02-28T12:53:47+01:00',
            ],
            'utc_with_100ms_second_fraction' => [
                'given' => '2022-02-28T13:23:47.100Z',
                'expectedAsRFC3339InBrussels' => '2022-02-28T14:23:47+01:00',
            ],
            'offset_with_7ms_second_fraction' => [
                'given' => '2022-02-28T13:23:47.007+01:00',
                'expectedAsRFC3339InBrussels' => '2022-02-28T13:23:47+01:00',
            ],
            'utc_with_5μs_second_fraction' => [
                'given' => '2022-02-28T13:23:47.000005Z',
                'expectedAsRFC3339InBrussels' => '2022-02-28T14:23:47+01:00',
            ],
            'offset_with_600μs_second_fraction' => [
                'given' => '2022-02-28T13:23:47.000600+01:00',
                'expectedAsRFC3339InBrussels' => '2022-02-28T13:23:47+01:00',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidISO8601DataProvider
     */
    public function it_throws_when_given_an_invalid_iso_8601_string(string $invalidDateTime): void
    {
        $this->expectException(DateTimeInvalid::class);
        DateTimeFactory::fromISO8601($invalidDateTime);
    }

    public function invalidISO8601DataProvider(): array
    {
        return [
            'no_timezone' => ['dateTime' => '2022-02-28T13:23:47'],
            'no_T' => ['dateTime' => '2022-02-28 13:23:47+01:30'],
            'just_a_date' => ['dateTime' => '2022-02-28'],
        ];
    }

    /**
     * @test
     * @dataProvider validAtomDataProvider
     */
    public function it_creates_a_date_time_object_from_a_valid_atom_string(string $given, string $expected): void
    {
        $object = DateTimeFactory::fromAtom($given)->setTimezone(new DateTimeZone('Europe/Brussels'));
        $datetime = $object->format(DATE_ATOM);
        $this->assertEquals($expected, $datetime);
    }

    /**
     * @test
     * @dataProvider validCdbFormats
     */
    public function it_creates_a_date_time_object_from_a_cdb_format(string $given, DateTimeImmutable $expected): void
    {
        $actual = DateTimeFactory::fromCdbFormat($given);
        $this->assertEquals(
            $expected,
            $actual
        );
    }

    public function validAtomDataProvider(): array
    {
        return [
            'utc' => [
                'given' => '2024-04-08T18:00:00Z',
                'expected' => '2024-04-08T20:00:00+02:00',
            ],
            'offset_positive' => [
                'given' => '2024-04-08T18:00:00+02:00',
                'expected' => '2024-04-08T18:00:00+02:00',
            ],
            'offset_negative' => [
                'given' => '2024-04-08T18:00:00-04:00',
                'expected' => '2024-04-09T00:00:00+02:00',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAtomDateTimeProvider
     */
    public function it_throws_when_given_an_invalid_atom_string(string $invalidDateTime): void
    {
        $this->expectException(DateTimeInvalid::class);
        DateTimeFactory::fromAtom($invalidDateTime);
    }

    public function invalidAtomDateTimeProvider(): array
    {
        return [
            'no_timezone' => ['dateTime' => '2022-02-28T13:23:47'],
            'no_T' => ['dateTime' => '2022-02-28 13:23:47+01:30'],
            'just_a_date' => ['dateTime' => '2022-02-28'],
            'zulu' => ['dateTime' => '2024-04-08T18:00:00.500Z'],
            'seconds' => ['dateTime' => '2024-04-08T18:00:00.250+01:00'],
        ];
    }

    public function validCdbFormats(): array
    {
        return [
            'T' => [
                'given' => '2014-06-30T11:48:26',
                'expected' => new DateTimeImmutable(
                    '2014-06-30T11:48:26',
                    new DateTimeZone('Europe/Brussels')
                ),
            ],
            'space' => [
                'given' => '2016-04-15 19:52:31',
                'expected' => new DateTimeImmutable(
                    '2016-04-15T19:52:31',
                    new DateTimeZone('Europe/Brussels')
                ),
            ],
        ];
    }
}
