<?php

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
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
    public function it_validates_incoming_data(): void
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

    private function buildRequestWithBody(array $body): Request
    {
        return new Request([], [], [], [], [], [], json_encode($body));
    }
}
