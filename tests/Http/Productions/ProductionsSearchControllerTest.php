<?php

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class ProductionsSearchControllerTest extends TestCase
{
    /**
     * @var ProductionRepository|MockObject
     */
    private $repository;

    /**
     * @var ProductionsSearchController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductionRepository::class);
        $this->controller = new ProductionsSearchController($this->repository);
    }

    /**
     * @test
     */
    public function it_returns_an_empty_result_(): void
    {
        $this->repository->expects($this->once())->method('search')->with('foo')->willReturn([]);
        $response = $this->controller->search(new Request(['name' => 'foo']));

        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @test
     */
    public function it_can_search_productions_by_name(): void
    {
        $productionId = ProductionId::generate();
        $name = 'Indiana Foo and the arrayders of the lost SPARC';
        $events = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString()
        ];

        $productions = [new Production($productionId, $name, $events),];
        $this->repository->expects($this->once())->method('search')->with('foo')->willReturn($productions);

        $response = $this->controller->search(new Request(['name' => 'foo']));

        $this->assertEquals(
            [
                [
                    'production_id' => $productionId->toNative(),
                    'name' => $name,
                    'events' => $events,
                ],
            ],
            json_decode($response->getContent(), true)
        );
    }
}
