<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DummyPlaceProjectionEnricherTest extends TestCase
{
    /**
     * @var DummyPlaceProjectionEnricher
     */
    private $enricher;

    /**
     * @var DocumentRepository|MockObject
     */
    private $repository;

    /**
     * @var string
     */
    private $dummyPlaceId;

    protected function setUp()
    {
        $this->dummyPlaceId = Uuid::uuid4()->toString();
        $this->repository = $this->createMock(DocumentRepository::class);
        $this->enricher = new DummyPlaceProjectionEnricher($this->repository, [$this->dummyPlaceId]);
    }

    /**
     * @test
     */
    public function it_should_ignore_events_for_non_dummy_places(): void
    {
        $id = Uuid::uuid4()->toString();
        $eventJson = $this->getEventJsonForPlace(Uuid::uuid4()->toString());
        $readModel = new JsonDocument($id, $eventJson);
        $this->repository->expects($this->once())->method('get')->with($id)->willReturn($readModel);
        $ignoredReadModel = $this->enricher->get($id);
        $this->assertEquals($readModel, $ignoredReadModel);
    }

    /**
     * @test
     */
    public function it_should_enrich_events_for_dummy_places(): void
    {
        $id = Uuid::uuid4()->toString();
        $eventJson = $this->getEventJsonForPlace($this->dummyPlaceId);
        $readModel = new JsonDocument($id, $eventJson);

        $this->repository->expects($this->once())->method('fetch')->with($id)->willReturn($readModel);
        $this->repository->expects($this->once())->method('get')->with($id)->willReturn($readModel);

        $fetchEnrichedReadModel = $this->enricher->fetch($id);
        $getEnrichedReadModel = $this->enricher->get($id);

        $this->assertNotEquals($readModel, $fetchEnrichedReadModel);
        $this->assertNotEquals($readModel, $getEnrichedReadModel);

        $this->assertTrue($fetchEnrichedReadModel->getBody()->isDummyPlaceForEducationEvents);
        $this->assertTrue($getEnrichedReadModel->getBody()->isDummyPlaceForEducationEvents);
    }

    private function getEventJsonForPlace(string $placeId): string
    {
        return json_encode(
            [
                '@id' => 'https://example.com/entity/' . $placeId,
            ]
        );
    }
}
