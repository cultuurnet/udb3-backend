<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var MappingRepository|MockObject
     */
    private $mappingRepository;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->repository = $this->createMock(EventRepository::class);
        $this->client = $this->createMock(KinepolisClient::class);
        $this->parser = $this->createMock(Parser::class);
        $this->mappingRepository = $this->createMock(MappingRepository::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->service = new KinepolisService(
            $this->commandBus,
            $this->repository,
            $this->client,
            $this->parser,
            $this->mappingRepository,
            $this->uuidGenerator
        );
    }

    /**
     * @test
     */
    public function it_will_only_call_a_token_once(): void
    {
        $this->client->expects($this->once())->method('getToken')->willReturn('dummyToken');
        $this->service->fetch();
    }

    /**
     * @test
     */
    public function it_will_get_a_detail_per_movieProduction(): void
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

        $this->service->fetch();
    }

    /**
     * @test
     */
    public function it_should_dispatch_every_parse_movie(): void
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
                        'Kinepolis:tDECAm123',
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
                        )
                    ),
                ]
            );

        $this->service->fetch();
        $this->assertEquals(
            [],
            $this->commandBus->getRecordedCommands()
        );
    }
}
