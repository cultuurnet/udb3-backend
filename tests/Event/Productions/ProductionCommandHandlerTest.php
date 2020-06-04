<?php

namespace CultuurNet\UDB3\Event\Productions;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class ProductionCommandHandlerTest extends TestCase
{
    /**
     * @var ProductionRepository | MockObject
     */
    private $productionRepository;

    /**
     * @var ProductionCommandHandler
     */
    private $commandHandler;

    protected function setUp(): void
    {
        $this->productionRepository = $this->createMock(ProductionRepository::class);
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

        $this->productionRepository->expects($this->once())->method('add')->willReturnCallback(
            function (Production $production) use ($name, $events) {
                $this->assertEquals($name, $production->getName());
                $this->assertEquals($events, $production->getEventIds());
            }
        );

        $this->commandHandler->handle($command);
    }
}
