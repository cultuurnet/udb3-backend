<?php

namespace CultuurNet\UDB3\Event\Productions;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ProductionTest extends TestCase
{
    /**
     * @test
     */
    public function production_name_cannot_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Production::createEmpty('   ');
    }

    /**
     * @test
     */
    public function exposes_its_name_and_identifier(): void
    {
        $productionId = ProductionId::generate();
        $name = 'The Ghost of Shamlet - To Pee or no to Pee';

        $production = new Production($productionId, $name, []);

        $this->assertEquals($productionId, $production->getProductionId());
        $this->assertEquals($name, $production->getName());
    }

    /**
     * @test
     */
    public function it_removes_excessive_spaces_from_the_name(): void
    {
        $name = 'The Scottish Play';
        $production = Production::createEmpty(' ' . $name . '   ');

        $this->assertEquals($name, $production->getName());
    }

    /**
     * @Test
     */
    public function it_knows_if_it_contains_a_certain_event(): void
    {
        $name = 'A Hose By Any Other Name - Gardening with Romeo & Juliet';
        $eventInProduction = Uuid::uuid4()->toString();
        $eventNotInProduction = Uuid::uuid4()->toString();
        $production = Production::createEmpty($name)->addEvent($eventInProduction);

        $this->assertTrue($production->containsEvent($eventInProduction));
        $this->assertFalse($production->containsEvent($eventNotInProduction));
    }
}
