<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Productions\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\DBALException;
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

    /**
     * @test
     */
    public function it_can_add_an_event_to_an_existing_production(): void
    {
        $production = $this->givenThereIsAProduction();
        $eventToAdd = Uuid::uuid4()->toString();
        $this->repository->addEvent($eventToAdd, $production);

        $persistedProduction = $this->repository->find($production->getProductionId());
        $this->assertTrue($persistedProduction->containsEvent($eventToAdd));
    }

    /**
     * @test
     */
    public function it_cannot_add_an_event_to_a_production_when_it_belongs_to_another_production(): void
    {
        $production = $this->givenThereIsAProduction('foo');
        $otherProduction = $this->givenThereIsAProduction('bar');
        $eventToAdd = $otherProduction->getEventIds()[0];

        $this->expectException(DBALException::class);
        $this->repository->addEvent($eventToAdd, $production);
    }

    private function givenThereIsAProduction(string $name = 'foo'): Production
    {
        $production = Production::createEmpty($name);
        $production = $production->addEvent(Uuid::uuid4()->toString());
        $production = $production->addEvent(Uuid::uuid4()->toString());

        $this->repository->add($production);

        return $production;
    }
}
