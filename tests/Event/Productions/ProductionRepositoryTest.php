<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Productions\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\DBALException;
use phpDocumentor\Reflection\Types\Void_;
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

    /**
     * @test
     */
    public function it_can_remove_event_from_production(): void
    {
        $production = $this->givenThereIsAProduction();
        $eventToKeep = $production->getEventIds()[0];
        $eventToRemove = $production->getEventIds()[1];

        $this->repository->removeEvent($eventToRemove, $production->getProductionId());

        $persistedProduction = $this->repository->find($production->getProductionId());
        $this->assertTrue($persistedProduction->containsEvent($eventToKeep));
        $this->assertFalse($persistedProduction->containsEvent($eventToRemove));
    }

    /**
     * @test
     */
    public function a_production_without_events_no_longer_exists(): void
    {
        $production = $this->givenThereIsAProduction();
        foreach ($production->getEventIds() as $eventId) {
            $this->repository->removeEvent($eventId, $production->getProductionId());
        }

        $this->expectException(EntityNotFoundException::class);
        $this->repository->find($production->getProductionId());
    }

    /**
     * @test
     */
    public function it_can_move_events_to_other_production(): void
    {
        $fromProduction = $this->givenThereIsAProduction('foo');
        $toProduction = $this->givenThereIsAProduction('bar');
        $eventsToMove = $fromProduction->getEventIds();

        $this->repository->moveEvents($fromProduction->getProductionId(), $toProduction);

        $resultingProduction = $this->repository->find($toProduction->getProductionId());
        foreach ($eventsToMove as $eventToMove) {
            $this->assertTrue($resultingProduction->containsEvent($eventToMove));
        }

        $this->expectException(EntityNotFoundException::class);
        $this->repository->find($fromProduction->getProductionId());
    }

    /**
     * @test
     */
    public function it_can_find_production_for_event(): void
    {
        $event = Uuid::uuid4()->toString();
        $otherEvent = Uuid::uuid4()->toString();
        $name = 'FooBar';

        $production = Production::createEmpty($name);
        $production = $production->addEvent($event);
        $production = $production->addEvent($otherEvent);
        $this->repository->add($production);

        $this->givenThereIsAProduction('OtherProduction');

        $production = $this->repository->findProductionForEventId($event);
        $this->assertEquals($name, $production->getName());
        $this->assertEquals([$event, $otherEvent], $production->getEventIds());
    }

    /**
     * @test
     */
    public function it_can_find_event_pairs_for_event_in_production()
    {
        $production = $this->givenThereIsAProduction();
        $pairs = $this->repository->findEventPairs($production->getEventIds()[0], $production->getProductionId());
        $this->assertCount(1, $pairs);
    }

    /**
     * @test
     */
    public function it_will_throw_if_when_finding_pairs_if_production_does_not_exists()
    {
        $this->expectException(EntityNotFoundException::class);

        $this->repository->findEventPairs(Uuid::uuid4()->toString(), Production::createEmpty('Some')->getProductionId());
    }

    /**
     * @test
     */
    public function it_will_throw_if_when_finding_pairs_if_event_is_not_in_production()
    {
        $production = $this->givenThereIsAProduction();

        $this->expectException(EntityNotFoundException::class);

        $this->repository->findEventPairs(Uuid::uuid4()->toString(), $production->getProductionId());
    }
    
    /**
     * @test
     */
    public function it_will_throw_if_it_cannot_find_production_for_event(): void
    {
        $randomEventId = Uuid::uuid4()->toString();

        $this->expectException(EntityNotFoundException::class);
        $this->repository->findProductionForEventId($randomEventId);
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
