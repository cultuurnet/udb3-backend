<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

final class KinepolisParserTest extends TestCase
{
    private KinepolisParser $parser;

    public function setUp(): void
    {
        $dateParser = $this->createMock(DateParser::class);

        $dateParser->method('processDates')
            ->willReturn([
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
            ], );
        $this->parser = new KinepolisParser(
            [
                616 => '1.7.2.0.0', // Actie | Actie en avontuur
                986 => '1.7.3.0.0', // Actiekomedie
                619 => '1.7.12.0.0', // Animatie | Animatie en kinderfilms
                993 => '1.7.12.0.0', // Animes
            ],
            [
                'KOOST' => 'b4ed748a-dfc4-432f-b242-ed1db62b76e2',
                'DECA' => 'cbf8ddad-9aa7-4add-9133-228a752a87a5',
            ],
            $dateParser
        );
    }

    /**
     * @test
     */
    public function it_will_return_an_array_of_parse_movies(): void
    {
        $this->assertEquals(
            [
                new ParsedMovie(
                    'Kinepolis:tDECAm32696',
                    new Title('Godzilla x Kong: The New Empire'),
                    new LocationId('cbf8ddad-9aa7-4add-9133-228a752a87a5'),
                    new Description(
                        'Het epische gevecht gaat verder! In het Monsterverse van Legendary Pictures' .
                        ' volgt na de sensationele krachtmeting van “Godzilla vs. Kong” nu een geheel nieuw avontuur ' .
                        'waarin de machtige Kong en de angstaanjagende Godzilla het opnemen tegen elkaar.'
                    ),
                    (new EventThemeResolver())->byId('1.7.2.0.0'),
                    new SingleSubEventCalendar(new SubEvent(
                        new DateRange(
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T18:00:00+00:00'),
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:00:00+00:00')
                        ),
                        new Status(StatusType::Available()),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    )),
                    new PriceInfo(
                        new Tariff(
                            new TranslatedTariffName(
                                new Language('nl'),
                                new TariffName('Basistarief')
                            ),
                            new Money(1100, new Currency('EUR'))
                        ),
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Kinepolis Student Card')
                                ),
                                new Money(900, new Currency('EUR'))
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Kortingstarief')
                                ),
                                new Money(1000, new Currency('EUR'))
                            )
                        )
                    )
                ),
                new ParsedMovie(
                    'Kinepolis:tKOOSTm32696',
                    new Title('Godzilla x Kong: The New Empire'),
                    new LocationId('b4ed748a-dfc4-432f-b242-ed1db62b76e2'),
                    new Description(
                        'Het epische gevecht gaat verder! In het Monsterverse van Legendary Pictures' .
                        ' volgt na de sensationele krachtmeting van “Godzilla vs. Kong” nu een geheel nieuw avontuur ' .
                        'waarin de machtige Kong en de angstaanjagende Godzilla het opnemen tegen elkaar.'
                    ),
                    (new EventThemeResolver())->byId('1.7.2.0.0'),
                    new SingleSubEventCalendar(new SubEvent(
                        new DateRange(
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T20:30:00+00:00'),
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T22:30:00+00:00')
                        ),
                        new Status(StatusType::Available()),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    )),
                    new PriceInfo(
                        new Tariff(
                            new TranslatedTariffName(
                                new Language('nl'),
                                new TariffName('Basistarief')
                            ),
                            new Money(1000, new Currency('EUR'))
                        ),
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Kinepolis Student Card')
                                ),
                                new Money(800, new Currency('EUR'))
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Kortingstarief')
                                ),
                                new Money(900, new Currency('EUR'))
                            )
                        )
                    )
                ),
                new ParsedMovie(
                    'Kinepolis:tKOOSTm32696v3D',
                    new Title('Godzilla x Kong: The New Empire 3D'),
                    new LocationId('b4ed748a-dfc4-432f-b242-ed1db62b76e2'),
                    new Description(
                        'Het epische gevecht gaat verder! In het Monsterverse van Legendary Pictures' .
                        ' volgt na de sensationele krachtmeting van “Godzilla vs. Kong” nu een geheel nieuw avontuur ' .
                        'waarin de machtige Kong en de angstaanjagende Godzilla het opnemen tegen elkaar.'
                    ),
                    (new EventThemeResolver())->byId('1.7.2.0.0'),
                    new SingleSubEventCalendar(new SubEvent(
                        new DateRange(
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T17:45:00+00:00'),
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2024-04-08T19:45:00+00:00')
                        ),
                        new Status(StatusType::Available()),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    )),
                    new PriceInfo(
                        new Tariff(
                            new TranslatedTariffName(
                                new Language('nl'),
                                new TariffName('Basistarief')
                            ),
                            new Money(1200, new Currency('EUR'))
                        ),
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Kinepolis Student Card')
                                ),
                                new Money(1000, new Currency('EUR'))
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Kortingstarief')
                                ),
                                new Money(1100, new Currency('EUR'))
                            )
                        )
                    )
                ),
            ],
            $this->parser->getParsedMovies(
                Json::decodeAssociatively(file_get_contents(__DIR__ . '/samples/KinepolisMovieDetailResponse.json')),
                [
                    'KOOST' => new ParsedPrice(
                        1000,
                        900,
                        800,
                        250,
                        200
                    ),
                    'DECA' => new ParsedPrice(
                        1100,
                        1000,
                        900,
                        300,
                        250
                    ),
                ]
            )
        );
    }
}
