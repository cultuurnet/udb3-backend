<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Productions\Doctrine\SchemaConfigurator;
use PHPUnit\Framework\TestCase;
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
    public function foo(): void
    {
        $production = Production::createEmpty('foo');
        $production = $production->addEvent('bar');
        $production = $production->addEvent('baz');

        $this->repository->add($production);
        $result = $this->repository->find($production->getProductionId());

        $this->assertEquals($production, $result);
    }
}
