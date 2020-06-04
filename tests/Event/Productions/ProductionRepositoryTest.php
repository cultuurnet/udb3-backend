<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Productions\Doctrine\SchemaConfigurator;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;
use ValueObjects\StringLiteral\StringLiteral;

class ProductionRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var ProductionRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $schema = new SchemaConfigurator();
        $schema->configure($this->getConnection()->getSchemaManager());
        $this->repository = new ProductionRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_can_persist_productions(): void
    {
        $production = $this->givenThereIsAProduction();
        $result = $this->repository->find($production->getProductionId());
        $this->assertEquals($production, $result);
    }

    private function givenThereIsAProduction(): Production
    {
        $production = Production::createEmpty($name);
        $production = $production->addEvent(Uuid::uuid4()->toString());
        $production = $production->addEvent(Uuid::uuid4()->toString());

        $this->repository->add($production);

        return $production;
    }
}
