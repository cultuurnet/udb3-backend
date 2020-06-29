<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class ProductionEnrichedEventRepositoryTest extends TestCase
{
    /**
     * @var ProductionEnrichedEventRepository
     */
    private $productionEnrichedEventRepository;

    /**
     * @var ProductionRepository | MockObject
     */
    private $productionRepository;

    /**
     * @var DocumentRepositoryInterface | MockObject
     */
    private $eventRepository;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->productionRepository = $this->createMock(ProductionRepository::class);

        $this->productionEnrichedEventRepository = new ProductionEnrichedEventRepository(
            $this->eventRepository,
            $this->productionRepository
        );
    }

    /**
     * @test
     */
    public function it_returns_a_null_production_when_the_event_does_not_belong_to_a_production(): void
    {
        $eventId = Uuid::uuid4()->toString();
        $originalJsonDocument = new JsonDocument(
            $eventId,
            json_encode((object) ['@id' => $eventId])
        );


        $this->eventRepository->method('get')->willReturn($originalJsonDocument);
        $this->productionRepository->method('findProductionForEventId')->willThrowException(
            new EntityNotFoundException()
        );

        $actual = $this->productionEnrichedEventRepository->get($eventId);

        $this->assertEquals($eventId, $actual->getBody()->{'@id'});
        $this->assertNull($actual->getBody()->production);
    }
}
