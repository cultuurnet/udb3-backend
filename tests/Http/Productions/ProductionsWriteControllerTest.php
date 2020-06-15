<?php

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Productions\AddEventToProduction;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class ProductionsWriteControllerTest extends TestCase
{
    /**
     * @var CommandBusInterface | MockObject
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
        $this->commandBus = $this->createMock(CommandBusInterface::class);
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

        $expectedEvents = [$eventId1, $eventId2];
        $this->commandBus->expects($this->once())->method('dispatch')->willReturnCallback(
            function (GroupEventsAsProduction $command) use ($name, $expectedEvents) {
                $this->assertEquals($name, $command->getName());
                $this->assertEquals($expectedEvents, $command->getEventIds());
            }
        );
        $this->controller->create($request);
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

        $this->commandBus->expects($this->never())->method('dispatch');
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
        $this->commandBus->expects($this->once())->method('dispatch')->willReturnCallback(
            function (AddEventToProduction $command) use ($productionId, $eventId) {
                $this->assertEquals($productionId, $command->getProductionId());
                $this->assertEquals($eventId, $command->getEventId());
            }
        );
        $this->controller->addEventToProduction($productionId->toNative(), $eventId);
    }

    private function buildRequestWithBody(array $body): Request
    {
        return new Request([], [], [], [], [], [], json_encode($body));
    }
}
