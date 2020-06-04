<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Productions\Doctrine\SchemaConfigurator;
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

    protected function setUp(): void
    {
        $schema = new SchemaConfigurator();
        $schema->configure($this->getConnection()->getSchemaManager());
        $this->productionRepository = new ProductionRepository($this->getConnection());
        $this->commandHandler = new ProductionCommandHandler($this->productionRepository);
    }

    /**
     * @test
     */
    public function itCanGroupEventsAsProduction(): void
    {
        $name = "A Midsummer Night's Scream";
        $events = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
        ];

        $command = new GroupEventsAsProduction($events, $name);
        $this->commandHandler->handle($command);

        $createdProduction = $this->productionRepository->find($command->getProductionId());
        $this->assertEquals($command->getProductionId(), $createdProduction->getProductionId());
        $this->assertEquals($name, $createdProduction->getName());
        $this->assertEquals($events, $createdProduction->getEventIds());
    }

    /**
     * @test
     */
    public function itCanAddEventToProduction(): void
    {
        $name = "A Midsummer Night's Scream 2";
        $eventToAdd = Uuid::uuid4()->toString();
        $events = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
        ];

        $command = new GroupEventsAsProduction($events, $name);
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
    public function itCannotAddAnEventThatAlreadyBelongsToAnotherProduction():void
    {
        $eventBelongingToFirstProduction = Uuid::uuid4()->toString();
        $name = "A Midsummer Night's Scream 2";
        $firstProductionCommand = new GroupEventsAsProduction([$eventBelongingToFirstProduction], $name);
        $this->commandHandler->handle($firstProductionCommand);

        $name = "A Midsummer Night's Scream 3";
        $secondProductionCommand = new GroupEventsAsProduction([Uuid::uuid4()->toString()], $name);
        $this->commandHandler->handle($secondProductionCommand);

        $this->expectException(EventCannotBeAddedToProduction::class);
        $this->commandHandler->handle(
            new AddEventToProduction($eventBelongingToFirstProduction, $secondProductionCommand->getProductionId())
        );
    }

    /**
     * @test
     */
    public function itCanRemoveAnEventFromAProduction(): void
    {
        $name = "A Midsummer Night's Scream 2";
        $eventToRemove = Uuid::uuid4()->toString();
        $events = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            $eventToRemove,
        ];

        $command = new GroupEventsAsProduction($events, $name);
        $this->commandHandler->handle($command);


        $this->commandHandler->handle(
            new RemoveEventFromProduction($eventToRemove, $command->getProductionId())
        );

        $production = $this->productionRepository->find($command->getProductionId());
        $this->assertFalse($production->containsEvent($eventToRemove));
        $this->assertCount(2, $production->getEventIds());
    }
}
