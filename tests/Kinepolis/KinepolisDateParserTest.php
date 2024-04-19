<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\Kinepolis\Parser\KinepolisDateParser;
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
                'data' => require __DIR__ . '/samples/MovieWithoutLength.php',
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
                        ],
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
                        ],
                    ],
                ],
            ],
            'movie_with_length' =>  [
                'data' => require  __DIR__ . '/samples/MovieWithLength.php',
                'length' => 99,
                'result' => [
                    'DECA' => [
                        '2D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T18:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T19:39:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:15:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T21:54:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T13:39:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T16:39:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ],
                    ],
                    'KOOST' => [
                        '2D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:30:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T22:09:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T17:45:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T19:24:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:15:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T13:54:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T16:39:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T20:30:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T22:09:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ],
                    ],
                ],
            ],
            'movie_with_2D_and_3D_version' => [
                'data' => require __DIR__ . '/samples/MovieWith2DAnd3DVersion.php',
                'length' => 120,
                'result' => [
                    'DECA' => [
                        '2D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T18:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T14:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T17:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ],
                        '3D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:15:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T22:15:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ],
                    ],
                    'KOOST' => [
                        '2D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:30:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T22:30:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T12:15:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T14:15:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T15:00:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T17:00:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T20:30:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-09T22:30:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ],
                        '3D' => [
                            new SubEvent(
                                new DateRange(
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T17:45:00+00:00'),
                                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T19:45:00+00:00')
                                ),
                                new Status(StatusType::Available()),
                                new BookingAvailability(BookingAvailabilityType::Available())
                            ),
                        ],
                    ],
                ],
            ],
        ];
    }
}
