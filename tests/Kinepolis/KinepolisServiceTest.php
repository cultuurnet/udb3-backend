<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Description as LegacyDescription;
use CultuurNet\UDB3\Event\Commands\AddImage;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\Productions\AddEventToProduction;
use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Kinepolis\Client\KinepolisClient;
use CultuurNet\UDB3\Kinepolis\Mapping\MappingRepository;
use CultuurNet\UDB3\Kinepolis\Parser\Parser;
use CultuurNet\UDB3\Kinepolis\Parser\PriceParser;
use CultuurNet\UDB3\Kinepolis\Trailer\TrailerRepository;
use CultuurNet\UDB3\Kinepolis\ValueObject\ParsedMovie;
use CultuurNet\UDB3\Kinepolis\ValueObject\ParsedPriceForATheater;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class KinepolisServiceTest extends TestCase
{
    private KinepolisService $service;

    private TraceableCommandBus $commandBus;

    /**
     * @var Repository|MockObject
     */
    private $repository;

    /**
     * @var KinepolisClient|MockObject
     */
    private $client;

    /**
     * @var Parser|MockObject
     */
    private $parser;

    /**
     * @var PriceParser|MockObject
     */
    private $priceParser;

    /**
     * @var MappingRepository|MockObject
     */
    private $mappingRepository;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var TrailerRepository|MockObject
     */
    private $trailerRepository;

    /**
     * @var ImageUploaderInterface|MockObject
     */
    private $imageUploader;

    /**
     * @var ProductionRepository|MockObject
     */
    private $productionRepository;

    private string $eventId;

    private string $movieId;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->repository = $this->createMock(EventRepository::class);
        $this->client = $this->createMock(KinepolisClient::class);
        $this->parser = $this->createMock(Parser::class);
        $this->priceParser = $this->createMock(PriceParser::class);
        $this->mappingRepository = $this->createMock(MappingRepository::class);
        $this->imageUploader = $this->createMock(ImageUploaderInterface::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->trailerRepository = $this->createMock(TrailerRepository::class);
        $this->productionRepository = $this->createMock(ProductionRepository::class);

        $this->service = new KinepolisService(
            $this->commandBus,
            $this->repository,
            $this->client,
            $this->parser,
            $this->priceParser,
            $this->mappingRepository,
            $this->imageUploader,
            $this->uuidGenerator,
            $this->trailerRepository,
            $this->productionRepository,
            $this->createMock(LoggerInterface::class)
        );

        $this->commandBus->record();

        $this->eventId = 'd1912df4-0b6b-401a-b77c-ae31d6d013bb';
        $this->movieId = 'Kinepolis:tDECAm123';
    }

    /**
     * @test
     */
    public function it_will_only_call_a_token_once(): void
    {
        $this->client->expects($this->once())->method('getToken')->willReturn('dummyToken');
        $this->service->import();
    }

    /**
     * @test
     */
    public function it_will_get_prices_foreach_theater(): void
    {
        $this->client
            ->expects($this->once())
            ->method('getTheaters')
            ->willReturn([
                0 => ['tid' => 'KOOST'],
                1 => ['tid' => 'DECA'],
            ]);

        $this->priceParser
            ->expects($this->exactly(2))
            ->method('parseTheaterPrices')
            ->willReturn(new ParsedPriceForATheater(
                0,
                0,
                0,
                0,
                0
            ));

        $this->client->expects($this->exactly(2))->method('getPricesForATheater');

        $this->service->import();
    }

    /**
     * @test
     */
    public function it_will_get_a_detail_per_movie_production(): void
    {
        $this->client
            ->expects($this->once())
            ->method('getMovies')
            ->willReturn([
                ['mid' => 1],
                ['mid' => 2],
                ['mid' => 3],
            ]);
        $this->client->expects($this->exactly(3))->method('getMovieDetail');

        $this->service->import();
    }

    /**
     * @test
     */
    public function it_dispatches_commands_for_newly_created_movie(): void
    {
        $now = Chronos::now();
        Chronos::setTestNow($now);

        $this->client
            ->expects($this->once())
            ->method('getMovies')
            ->willReturn([
                ['mid' => 1],
            ]);

        $this->client
            ->expects($this->once())
            ->method('getMovieDetail');

        $this->parser
            ->expects($this->once())
            ->method('getParsedMovies')
            ->willReturn(
                [
                    new ParsedMovie(
                        $this->movieId,
                        new Title('Het Smelt'),
                        new LocationId('a77c8b8e-41e5-44cf-9407-f809ebb48744'),
                        new Description('Eva groeit samen met twee jongens op in het kleine dorp Bovenmeer.'),
                        (new EventThemeResolver())->byId('1.7.4.0.0'),
                        new MultipleSubEventsCalendar(
                            new SubEvents(
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
                            ),
                        ),
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
                                ),
                            )
                        ),
                        '/MovieService/cdn.kinepolis.be/images/BE/65459BAD-CA99-4711-A97B-E049A5FA94E2/HO00010201/0000024162/Het_Smelt.jpg'
                    ),
                ]
            );

        $this->mappingRepository
            ->expects($this->once())
            ->method('getByMovieId')
            ->with($this->movieId)
            ->willReturn(null);

        $this->uuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($this->eventId);

        $this->mappingRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->eventId, $this->movieId);

        $this->repository->expects($this->once())->method('save');

        $imageId = new UUID('a05ca76d-0ccd-456c-97a2-b96859671d5e');
        $this->imageUploader
            ->expects($this->once())
            ->method('upload')
            ->willReturn($imageId);

        $productionId = ProductionId::generate();
        $this->productionRepository
            ->expects($this->once())
            ->method('search')
            ->with('Het Smelt', 0, 1)
            ->willReturn(
                [
                    new Production($productionId, 'Het Smelt', []),
                ]
            );

        $video = new Video(
            'da45a110-b404-4bd8-9827-27be0af471d2',
            new Url('https://www.youtube.com/watch?v=26r2alNpYSg'),
            new Language('nl')
        );
        $this->trailerRepository
            ->expects($this->once())
            ->method('search')
            ->with('Het Smelt')
            ->willReturn($video);

        $this->service->import();
        $this->assertEquals(
            [
                new Publish($this->eventId),
                new AddImage(
                    $this->eventId,
                    $imageId
                ),
                new AddEventToProduction(
                    $this->eventId,
                    $productionId
                ),
                new AddVideo(
                    $this->eventId,
                    $video
                ),
                new UpdateDescription(
                    $this->eventId,
                    new LegacyLanguage('nl'),
                    new LegacyDescription('Eva groeit samen met twee jongens op in het kleine dorp Bovenmeer.')
                ),
                new UpdatePriceInfo(
                    $this->eventId,
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
                            ),
                        )
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_dispatches_commands_for_an_updated_movie(): void
    {
        $this->client
            ->expects($this->once())
            ->method('getMovies')
            ->willReturn([
                ['mid' => 1],
            ]);

        $this->client
            ->expects($this->once())
            ->method('getMovieDetail');

        $this->parser
            ->expects($this->once())
            ->method('getParsedMovies')
            ->willReturn(
                [
                    new ParsedMovie(
                        $this->movieId,
                        new Title('Het Smelt'),
                        new LocationId('a77c8b8e-41e5-44cf-9407-f809ebb48744'),
                        new Description('Eva groeit samen met twee jongens op in het kleine dorp Bovenmeer.'),
                        (new EventThemeResolver())->byId('1.7.4.0.0'),
                        new MultipleSubEventsCalendar(
                            new SubEvents(
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
                            )
                        ),
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
                                ),
                            )
                        ),
                        '/MovieService/cdn.kinepolis.be/images/BE/65459BAD-CA99-4711-A97B-E049A5FA94E2/HO00010201/0000024162/Het_Smelt.jpg'
                    ),
                ]
            );

        $this->mappingRepository
            ->expects($this->once())
            ->method('getByMovieId')
            ->with($this->movieId)
            ->willReturn($this->eventId);

        $this->uuidGenerator
            ->expects($this->never())
            ->method('generate');

        $this->mappingRepository
            ->expects($this->never())
            ->method('create');

        $this->repository
            ->expects($this->never())
            ->method('save');

        $this->imageUploader
            ->expects($this->never())
            ->method('upload');

        $this->trailerRepository
            ->expects($this->never())
            ->method('search');

        $this->service->import();
        $this->assertEquals(
            [
                new UpdateCalendar(
                    $this->eventId,
                    Calendar::fromUdb3ModelCalendar(
                        new MultipleSubEventsCalendar(
                            new SubEvents(
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
                            )
                        )
                    )
                ),
                new UpdateDescription(
                    $this->eventId,
                    new LegacyLanguage('nl'),
                    new LegacyDescription('Eva groeit samen met twee jongens op in het kleine dorp Bovenmeer.')
                ),
                new UpdatePriceInfo(
                    $this->eventId,
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
                            ),
                        )
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
