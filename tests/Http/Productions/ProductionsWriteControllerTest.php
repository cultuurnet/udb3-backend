<?php

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Productions\AddEventToProduction;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Event\Productions\MergeProductions;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RemoveEventFromProduction;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class ProductionsWriteControllerTest extends TestCase
{
    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var ProductionsWriteController
     */
    private $controller;

    /**
     * @var CreateProductionValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->validator = new CreateProductionValidator();
        $this->controller = new ProductionsWriteController(
            $this->commandBus,
            $this->validator
        );
    }

    /**
     * @test
     */
    public function it_can_create_production(): void
    {
        $eventId1 = Uuid::uuid4()->toString();
        $eventId2 = Uuid::uuid4()->toString();
        $name = 'Singing in the drain';

        $request = $this->buildRequestWithBody(
            [
                'name' => $name,
                'eventIds' => [
                    $eventId1,
                    $eventId2,
                ]
            ]
        );

        $this->commandBus->record();
        $this->controller->create($request);

        $this->assertCount(1, $this->commandBus->getRecordedCommands());
        $recordedCommand = $this->commandBus->getRecordedCommands()[0];
        $this->assertInstanceOf(GroupEventsAsProduction::class, $recordedCommand);
        $this->assertEquals($name, $recordedCommand->getName());
        $this->assertEquals([$eventId1, $eventId2], $recordedCommand->getEventIds());
    }

    /**
     * @test
     */
    public function it_validates_incoming_data_to_create_a_production(): void
    {
        $request = $this->buildRequestWithBody(
            [
                'name' => '',
                'eventIds' => []
            ]
        );

        $this->expectException(DataValidationException::class);
        $this->controller->create($request);
    }

    /**
     * @test
     */
    public function it_can_add_an_event_to_an_existing_production(): void
    {
        $productionId = ProductionId::generate();
        $eventId = Uuid::uuid4();

        $this->commandBus->record();
        $this->controller->addEventToProduction($productionId->toNative(), $eventId);

        $this->assertEquals(
            [new AddEventToProduction($eventId, $productionId)],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_can_remove_an_event_from_an_existing_production(): void
    {
        $productionId = ProductionId::generate();
        $eventId = Uuid::uuid4();

        $this->commandBus->record();
        $this->controller->removeEventFromProduction($productionId->toNative(), $eventId);

        $this->assertEquals(
            [new RemoveEventFromProduction($eventId, $productionId)],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_can_merge_productions(): void
    {
        $fromProductionId = ProductionId::generate();
        $toProductionId = ProductionId::generate();

        $this->commandBus->record();
        $this->controller->mergeProductions($toProductionId->toNative(), $fromProductionId->toNative());

        $this->assertEquals(
            [new MergeProductions($fromProductionId, $toProductionId)],
            $this->commandBus->getRecordedCommands()
        );
    }

    private function buildRequestWithBody(array $body): Request
    {
        return new Request([], [], [], [], [], [], json_encode($body));
    }
}
