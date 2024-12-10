<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class DummyPlaceProjectionEnricherTest extends TestCase
{
    private DummyPlaceProjectionEnricher $enricher;

    /**
     * @var DocumentRepository&MockObject
     */
    private $repository;

    private string $dummyPlaceId;

    protected function setUp(): void
    {
        $this->dummyPlaceId = UUID::uuid4()->toString();
        $this->repository = $this->createMock(DocumentRepository::class);
        $this->enricher = new DummyPlaceProjectionEnricher($this->repository, [$this->dummyPlaceId]);
    }

    /**
     * @test
     */
    public function it_should_ignore_events_for_non_dummy_places(): void
    {
        $id = UUID::uuid4()->toString();
        $eventJson = $this->getEventJsonForPlace(UUID::uuid4()->toString());
        $readModel = new JsonDocument($id, $eventJson);
        $this->repository->expects($this->once())->method('fetch')->with($id)->willReturn($readModel);
        $ignoredReadModel = $this->enricher->fetch($id);
        $this->assertEquals($readModel, $ignoredReadModel);
    }

    /**
     * @test
     */
    public function it_should_enrich_events_for_dummy_places(): void
    {
        $id = UUID::uuid4()->toString();
        $eventJson = $this->getEventJsonForPlace($this->dummyPlaceId);
        $readModel = new JsonDocument($id, $eventJson);

        $this->repository->expects($this->once())->method('fetch')->with($id)->willReturn($readModel);

        $fetchEnrichedReadModel = $this->enricher->fetch($id);

        $this->assertNotEquals($readModel, $fetchEnrichedReadModel);

        $this->assertTrue($fetchEnrichedReadModel->getBody()->isDummyPlaceForEducationEvents);
    }

    private function getEventJsonForPlace(string $placeId): string
    {
        return Json::encode(
            [
                '@id' => 'https://example.com/entity/' . $placeId,
            ]
        );
    }
}
