<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Productions\Doctrine\ProductionSchemaConfigurator;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class ProductionCommandHandlerTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var ProductionRepository
     */
    private $productionRepository;

    /**
     * @var ProductionCommandHandler
     */
    private $commandHandler;

    /**
     * @var SimilaritiesClient|MockObject
     */
    private $similaritiesClient;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $eventRepository;

    protected function setUp(): void
    {
        $schema = $this->createSchema();
        $this->createTable(
            ProductionSchemaConfigurator::getTableDefinition($schema)
        );

        $this->productionRepository = new ProductionRepository($this->getConnection());
        $this->similaritiesClient = $this->createMock(SimilaritiesClient::class);
        $this->eventRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->commandHandler = new ProductionCommandHandler(
            $this->productionRepository,
            $this->similaritiesClient,
            $this->eventRepository
        );
    }

    /**
     * @test
     */
    public function it_can_group_events_as_production(): void
    {
        $name = "A Midsummer Night's Scream";
        $events = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
        ];

        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $this->similaritiesClient->expects(self::any())
            ->method('excludeTemporarily');

        $command = GroupEventsAsProduction::withProductionName($events, $name);
        $this->commandHandler->handle($command);

        $createdProduction = $this->productionRepository->find($command->getProductionId());
        $this->assertEquals($command->getProductionId(), $createdProduction->getProductionId());
        $this->assertEquals($name, $createdProduction->getName());
        $this->assertEquals($events, $createdProduction->getEventIds());
    }

    /**
     * @test
     */
    public function it_will_not_group_events_as_production_when_event_already_belongs_to_production(): void
    {
        $event = Uuid::uuid4()->toString();

        $name = "A Midsummer Night's Scream";
        $events = [
            $event,
            Uuid::uuid4()->toString(),
        ];

        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $command = GroupEventsAsProduction::withProductionName($events, $name);
        $this->commandHandler->handle($command);


        $this->expectException(EventCannotBeAddedToProduction::class);
        $this->commandHandler->handle(
            GroupEventsAsProduction::withProductionName(
                [
                    $event,
                    Uuid::uuid4()->toString(),
                ],
                'Some other production'
            )
        );
    }

    /**
     * @test
     */
    public function it_will_not_group_events_as_production_when_event_does_not_exist(): void
    {
        $event = Uuid::uuid4()->toString();

        $this->eventRepository->method('get')->willReturn(null);
        $this->expectException(EventCannotBeAddedToProduction::class);
        $this->commandHandler->handle(
            GroupEventsAsProduction::withProductionName(
                [
                    $event,
                    Uuid::uuid4()->toString(),
                ],
                'Some production'
            )
        );
    }

    /**
     * @test
     */
    public function it_can_add_event_to_production(): void
    {
        $name = "A Midsummer Night's Scream 2";
        $eventToAdd = Uuid::uuid4()->toString();
        $events = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
        ];

        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $this->similaritiesClient->expects(self::any())
            ->method('excludeTemporarily');

        $command = GroupEventsAsProduction::withProductionName($events, $name);
        $this->commandHandler->handle($command);


        $this->commandHandler->handle(
            new AddEventToProduction($eventToAdd, $command->getProductionId())
        );

        $production = $this->productionRepository->find($command->getProductionId());
        foreach ($events as $event) {
            $this->assertTrue($production->containsEvent($event));
        }
        $this->assertTrue($production->containsEvent($eventToAdd));
    }

    /**
     * @test
     */
    public function it_cannot_add_an_event_that_already_belongs_to_another_production(): void
    {
        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $eventBelongingToFirstProduction = Uuid::uuid4()->toString();
        $name = "A Midsummer Night's Scream 2";
        $firstProductionCommand = GroupEventsAsProduction::withProductionName([$eventBelongingToFirstProduction],
            $name);
        $this->commandHandler->handle($firstProductionCommand);

        $name = "A Midsummer Night's Scream 3";
        $secondProductionCommand = GroupEventsAsProduction::withProductionName([Uuid::uuid4()->toString()], $name);
        $this->commandHandler->handle($secondProductionCommand);

        $this->expectException(EventCannotBeAddedToProduction::class);
        $this->commandHandler->handle(
            new AddEventToProduction($eventBelongingToFirstProduction, $secondProductionCommand->getProductionId())
        );
    }

    /**
     * @test
     */
    public function it_cannot_add_a_non_existing_event_to_a_production(): void
    {
        $eventId = Uuid::uuid4()->toString();
        $this->eventRepository->method('get')->with($eventId)->willReturn(null);

        $this->expectException(EventCannotBeAddedToProduction::class);
        $this->commandHandler->handle(
            new AddEventToProduction($eventId, ProductionId::generate())
        );
    }

    /**
     * @test
     */
    public function it_can_remove_an_event_from_a_production(): void
    {
        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $name = "A Midsummer Night's Scream 2";
        $eventToRemove = Uuid::uuid4()->toString();
        $events = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            $eventToRemove,
        ];

        $command = GroupEventsAsProduction::withProductionName($events, $name);
        $this->commandHandler->handle($command);


        $this->commandHandler->handle(
            new RemoveEventFromProduction($eventToRemove, $command->getProductionId())
        );

        $production = $this->productionRepository->find($command->getProductionId());
        $this->assertFalse($production->containsEvent($eventToRemove));
        $this->assertCount(2, $production->getEventIds());
    }

    /**
     * @test
     */
    public function it_will_not_remove_events_from_another_production()
    {
        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $eventBelongingToFirstProduction = Uuid::uuid4()->toString();
        $name = "A Midsummer Night's Scream 2";
        $firstProductionCommand = GroupEventsAsProduction::withProductionName([$eventBelongingToFirstProduction],
            $name);
        $this->commandHandler->handle($firstProductionCommand);

        $eventBelongingToSecondProduction = Uuid::uuid4()->toString();
        $name = "A Midsummer Night's Scream 3";
        $secondProductionCommand = GroupEventsAsProduction::withProductionName([$eventBelongingToSecondProduction],
            $name);
        $this->commandHandler->handle($secondProductionCommand);

        $this->commandHandler->handle(
            new RemoveEventFromProduction($eventBelongingToFirstProduction, $secondProductionCommand->getProductionId())
        );

        $firstProduction = $this->productionRepository->find($firstProductionCommand->getProductionId());
        $this->assertTrue($firstProduction->containsEvent($eventBelongingToFirstProduction));

        $secondProduction = $this->productionRepository->find($secondProductionCommand->getProductionId());
        $this->assertTrue($secondProduction->containsEvent($eventBelongingToSecondProduction));
    }

    /**
     * @test
     */
    public function it_can_merge_productions()
    {
        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $event1 = Uuid::uuid4()->toString();
        $name = "I know what you did last Midsummer Night";
        $fromProductionCommand = GroupEventsAsProduction::withProductionName([$event1], $name);
        $this->commandHandler->handle($fromProductionCommand);

        $event2 = Uuid::uuid4()->toString();
        $name = "I know what you did last Midsummer Night's Dream";
        $toProductionCommand = GroupEventsAsProduction::withProductionName([$event2], $name);
        $this->commandHandler->handle($toProductionCommand);

        $this->similaritiesClient->expects(self::any())
            ->method('excludeTemporarily');

        $this->commandHandler->handle(
            new MergeProductions($fromProductionCommand->getProductionId(), $toProductionCommand->getProductionId())
        );

        $resultingProduction = $this->productionRepository->find($toProductionCommand->getProductionId());
        $this->assertTrue($resultingProduction->containsEvent($event1));
        $this->assertTrue($resultingProduction->containsEvent($event2));

        $this->expectException(EntityNotFoundException::class);
        $this->productionRepository->find($fromProductionCommand->getProductionId());
    }

    /**
     * @test
     */
    public function it_will_not_merge_to_unknown_production()
    {
        $this->eventRepository->method('get')->willReturn(new JsonDocument('foo'));

        $event1 = Uuid::uuid4()->toString();
        $name = "I know what you did last Midsummer Night";
        $fromProductionCommand = GroupEventsAsProduction::withProductionName([$event1], $name);
        $this->commandHandler->handle($fromProductionCommand);

        $toProductionId = ProductionId::generate();

        $this->expectException(EntityNotFoundException::class);
        $this->commandHandler->handle(
            new MergeProductions($fromProductionCommand->getProductionId(), $toProductionId)
        );
    }

    /**
     * @test
     */
    public function it_can_mark_events_as_skipped()
    {
        $eventPair = SimilarEventPair::fromArray([
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
        ]);

        $this->similaritiesClient->expects(self::atLeastOnce())
            ->method('excludePermanently')
            ->with([$eventPair]);

        $command = new RejectSuggestedEventPair($eventPair);
        $this->commandHandler->handle($command);
    }
}
