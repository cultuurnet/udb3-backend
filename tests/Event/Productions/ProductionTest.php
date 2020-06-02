<?php

namespace CultuurNet\UDB3\Event\Productions;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProductionTest extends TestCase
{
    /**
     * @test
     */
    public function production_name_cannot_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Production::create('   ');
    }

    /**
     * @test
     */
    public function exposes_its_name_and_identifier(): void
    {
        $productionId = ProductionId::generate();
        $name = 'The Ghost of Shamlet - To Pee or no to Pee';

        $production = new Production($productionId, $name);

        $this->assertEquals($productionId, $production->getProductionId());
        $this->assertEquals($name, $production->getName());
    }

    /**
     * @test
     */
    public function it_removes_excessive_spaces_from_the_name(): void
    {
        $name = 'The Scottish Play';
        $production = Production::create(' ' . $name . '   ');

        $this->assertEquals($name,  $production->getName());
    }
}
