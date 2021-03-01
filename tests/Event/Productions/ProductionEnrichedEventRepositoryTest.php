<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

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
     * @var IriGeneratorInterface | MockObject
     */
    private $iriGenerator;

    /**
     * @var DocumentRepository | MockObject
     */
    private $eventRepository;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(DocumentRepository::class);
        $this->productionRepository = $this->createMock(ProductionRepository::class);
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);

        $this->productionEnrichedEventRepository = new ProductionEnrichedEventRepository(
            $this->eventRepository,
            $this->productionRepository,
            $this->iriGenerator
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


        $this->eventRepository->method('fetch')->with($eventId)->willReturn($originalJsonDocument);
        $this->eventRepository->method('get')->with($eventId)->willReturn($originalJsonDocument);

        $this->productionRepository->method('findProductionForEventId')->willThrowException(
            new EntityNotFoundException()
        );

        $fetchActual = $this->productionEnrichedEventRepository->fetch($eventId);
        $getActual = $this->productionEnrichedEventRepository->get($eventId);

        $this->assertEquals($eventId, $fetchActual->getBody()->{'@id'});
        $this->assertEquals($eventId, $getActual->getBody()->{'@id'});

        $this->assertNull($fetchActual->getBody()->production);
        $this->assertNull($getActual->getBody()->production);
    }

    /**
     * @test
     */
    public function it_returns_production_data_when_event_belongs_to_a_production(): void
    {
        $eventId = Uuid::uuid4()->toString();
        $originalJsonDocument = new JsonDocument(
            $eventId,
            json_encode((object) ['@id' => $eventId])
        );

        $otherEventId = Uuid::uuid4()->toString();
        $productionId = ProductionId::generate();
        $productionName = 'The Teenage Mutant Ninja String Quartet in Concert - Heroes in a half Cello';
        $production = new Production(
            $productionId,
            $productionName,
            [
                $eventId,
                $otherEventId,
            ]
        );

        $this->eventRepository->method('fetch')->with($eventId)->willReturn($originalJsonDocument);
        $this->eventRepository->method('get')->with($eventId)->willReturn($originalJsonDocument);
        $this->productionRepository->method('findProductionForEventId')->willReturn($production);
        $this->iriGenerator->method('iri')->with($otherEventId)->willReturn('foo/' . $otherEventId);

        $fetchActual = $this->productionEnrichedEventRepository->fetch($eventId);
        $getActual = $this->productionEnrichedEventRepository->get($eventId);

        $this->assertEquals($eventId, $fetchActual->getBody()->{'@id'});
        $this->assertEquals($eventId, $getActual->getBody()->{'@id'});

        $this->assertEquals($productionId->toNative(), $fetchActual->getBody()->production->id);
        $this->assertEquals($productionId->toNative(), $getActual->getBody()->production->id);

        $this->assertEquals($productionName, $fetchActual->getBody()->production->title);
        $this->assertEquals($productionName, $getActual->getBody()->production->title);

        $this->assertEquals(['foo/' . $otherEventId], $fetchActual->getBody()->production->otherEvents);
        $this->assertEquals(['foo/' . $otherEventId], $getActual->getBody()->production->otherEvents);
    }

    /**
     * @test
     */
    public function it_will_return_null_when_getting_a_non_existing_document(): void
    {
        $eventId = Uuid::uuid4()->toString();
        $this->eventRepository->method('get')->willReturn(null);
        $actual = $this->productionEnrichedEventRepository->get($eventId);
        $this->assertNull($actual);
    }
}
