<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class DummyPlaceProjectionEnricherTest extends TestCase
{
    /**
     * @var DummyPlaceProjectionEnricher
     */
    private $enricher;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var string
     */
    private $dummyPlaceId;

    protected function setUp()
    {
        $this->dummyPlaceId = Uuid::uuid4()->toString();
        $this->repository = $this->createMock(DocumentRepositoryInterface::class);
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
        $this->repository->expects($this->once())->method('get')->with($id)->willReturn($readModel);
        $enrichedReadModel = $this->enricher->get($id);
        $this->assertNotEquals($readModel, $enrichedReadModel);
        $this->assertTrue($enrichedReadModel->getBody()->isDummyPlaceForEducationEvents);
    }

    private function getEventJsonForPlace(string $placeId): string
    {
        return json_encode(
            [
                'place' => [
                    '@id' => 'https://example.com/entity/' . $placeId,
                ],
            ]
        );
    }
}
