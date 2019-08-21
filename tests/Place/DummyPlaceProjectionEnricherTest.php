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

    protected function setUp()
    {
        $this->repository = $this->createMock(DocumentRepositoryInterface::class);
        $this->enricher = new DummyPlaceProjectionEnricher($this->repository);
    }

    /**
     * @test
     */
    public function it_should_call_the_repository()
    {
        $id = Uuid::uuid4()->toString();
        $this->repository->expects($this->once())->method('get')->with($id);
        $this->enricher->get($id);
    }
}
