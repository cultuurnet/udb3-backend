<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
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
    private $dummyLocationId;

    protected function setUp()
    {
        $this->dummyLocationId = Uuid::uuid4()->toString();
        $this->repository = $this->createMock(DocumentRepositoryInterface::class);
        $this->enricher = new DummyPlaceProjectionEnricher($this->repository, [$this->dummyLocationId]);
    }

    /**
     * @test
     */
    public function it_should_call_the_repository(): void
    {
        $id = Uuid::uuid4()->toString();
        $this->repository->expects($this->once())->method('get')->with($id);
        $this->enricher->get($id);
    }

    /**
     * @test
     */
    public function it_should_ignore_events_for_non_dummy_locations(): void
    {
        $id = Uuid::uuid4()->toString();
        $eventJson = $this->getEventJsonForLocation(Uuid::uuid4()->toString());
        $this->repository->expects($this->once())->method('get')->with($id)->willReturn($eventJson);
        $enrichedJson = $this->enricher->get($id);
        $this->assertEquals($eventJson, $enrichedJson);
    }

    private function getEventJsonForLocation(string $locationId): string
    {
        return json_encode(
            [
                'foo' => 'bar',
            ]
        );
    }
}
