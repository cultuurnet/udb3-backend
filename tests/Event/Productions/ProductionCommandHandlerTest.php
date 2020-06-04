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
}
