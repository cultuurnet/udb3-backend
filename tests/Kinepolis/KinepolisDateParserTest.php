<?php

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use PHPUnit\Framework\TestCase;

final class KinepolisDateParserTest extends TestCase
{
    private KinepolisDateParser $dateParser;

    public function setUp(): void
    {
        $this->dateParser = new KinepolisDateParser();
    }

    /**
     * @test
     * @dataProvider dateDataProvider
     */
    public function it_converts_dates_from_kinepolis(array $data, int $length, array $result): void
    {
        $this->assertEquals(
            $this->dateParser->processDates($data, $length),
            $result
        );
    }

    public function dateDataProvider(): array
    {
        return [
            'movie_without_length' => [
                'data' => [
                    '2024-04-08' =>
                        [
                            0 =>
                                [
                                    'prid' => '1008179023',
                                    'tid' => 'DECA',
                                    'screen' => 5,
                                    'time' => '20:00:00',
                                    'variant' => 'HO00010442',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 62,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 75,
                                        ],
                                    'format' =>
                                        [
                                            0 => 824,
                                        ],
                                    'language' => 'NL',
                                ],
                            1 =>
                                [
                                    'prid' => '1008179096',
                                    'tid' => 'DECA',
                                    'screen' => 8,
                                    'time' => '22:15:00',
                                    'variant' => 'HO00010201',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 61,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 72,
                                        ],
                                    'format' =>
                                        [
                                            0 => 824,
                                        ],
                                    'language' => 'NL',
                                ],
                            2 =>
                                [
                                    'prid' => '100984420',
                                    'tid' => 'KOOST',
                                    'screen' => 6,
                                    'time' => '22:30:00',
                                    'variant' => 'HO00010201',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 61,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 72,
                                        ],
                                    'format' =>
                                        [
                                            0 => 82,
                                        ],
                                    'language' => 'NL',
                                ],
                            3 =>
                                [
                                    'prid' => '100984428',
                                    'tid' => 'KOOST',
                                    'screen' => 8,
                                    'time' => '19:45:00',
                                    'variant' => 'HO00010201',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 61,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 72,
                                        ],
                                    'format' =>
                                        [
                                            0 => 824,
                                        ],
                                    'language' => 'NL',
                                ],
                        ],
                    '2024-04-09' =>
                        [
                            0 =>
                                [
                                    'prid' => '1008179073',
                                    'tid' => 'DECA',
                                    'screen' => 7,
                                    'time' => '14:00:00',
                                    'variant' => 'HO00010442',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 62,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 75,
                                        ],
                                    'format' =>
                                        [
                                            0 => 824,
                                        ],
                                    'language' => 'NL',
                                ],
                            1 =>
                                [
                                    'prid' => '1008179074',
                                    'tid' => 'DECA',
                                    'screen' => 7,
                                    'time' => '17:00:00',
                                    'variant' => 'HO00010442',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 62,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 75,
                                        ],
                                    'format' =>
                                        [
                                            0 => 824,
                                        ],
                                    'language' => 'NL',
                                ],
                            2 =>
                                [
                                    'prid' => '100984594',
                                    'tid' => 'KOOST',
                                    'screen' => 6,
                                    'time' => '14:15:00',
                                    'variant' => 'HO00010201',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 61,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 72,
                                        ],
                                    'format' =>
                                        [
                                            0 => 824,
                                        ],
                                    'language' => 'NL',
                                ],
                            3 =>
                                [
                                    'prid' => '100984595',
                                    'tid' => 'KOOST',
                                    'screen' => 6,
                                    'time' => '17:00:00',
                                    'variant' => 'HO00010201',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 61,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 72,
                                        ],
                                    'format' =>
                                        [
                                            0 => 82,
                                        ],
                                    'language' => 'NL',
                                ],
                            4 =>
                                [
                                    'prid' => '100984597',
                                    'tid' => 'KOOST',
                                    'screen' => 6,
                                    'time' => '22:30:00',
                                    'variant' => 'HO00010201',
                                    'saleable' => 1,
                                    'soldout' => 0,
                                    'time_saleable' => NULL,
                                    'version' =>
                                        [
                                            0 => 61,
                                        ],
                                    'sub' =>
                                        [
                                            0 => 72,
                                        ],
                                    'format' =>
                                        [
                                            0 => 82,
                                        ],
                                    'language' => 'NL',
                                ],
                        ],
                ],
                'length' => 0,
                'result' => [
                    'DECA' => [
                        '2D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T18:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T18:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:15:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:15:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ]
                    ],
                    'KOOST' => [
                        '2D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:30:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:30:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T17:45:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T17:45:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:15:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:15:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T20:30:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T20:30:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ]
                    ]
                ]
            ],
            'two' => [
                'data' => [],
                'length' => 0,
                'result' => []
            ],
        ];
    }
}
