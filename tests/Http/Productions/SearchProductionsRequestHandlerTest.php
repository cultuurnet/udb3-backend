<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class SearchProductionsRequestHandlerTest extends TestCase
{
    private ProductionRepository&MockObject $repository;

    private SearchProductionsRequestHandler $searchProductionsRequestHandler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductionRepository::class);
        $this->searchProductionsRequestHandler = new SearchProductionsRequestHandler($this->repository);
    }

    /**
     * @test
     */
    public function it_returns_an_empty_result_if_the_total_count_is_zero(): void
    {
        $this->repository->expects($this->once())
            ->method('count')
            ->with('foo')
            ->willReturn(0);
        $this->repository->expects($this->never())
            ->method('search');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/productions?name=foo')
            ->build('GET');

        $response = $this->searchProductionsRequestHandler->handle($request);

        $this->assertEquals(
            [
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 30,
                'totalItems' => 0,
                'member' => [],
            ],
            Json::decodeAssociatively($response->getBody()->getContents())
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_result_if_the_total_count_is_less_than_the_start(): void
    {
        $this->repository->expects($this->once())
            ->method('count')
            ->with('foo')
            ->willReturn(100);
        $this->repository->expects($this->never())
            ->method('search');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/productions?name=foo&start=101')
            ->build('GET');

        $response = $this->searchProductionsRequestHandler->handle($request);

        $this->assertEquals(
            [
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 30,
                'totalItems' => 100,
                'member' => [],
            ],
            Json::decodeAssociatively($response->getBody()->getContents())
        );
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
            Uuid::uuid4()->toString(),
        ];

        $productions = [new Production($productionId, $name, $events)];
        $this->repository->expects($this->once())
            ->method('count')
            ->with('foo')
            ->willReturn(43);
        $this->repository->expects($this->once())
            ->method('search')
            ->with('foo', 30, 15)
            ->willReturn($productions);

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/productions?name=foo&start=30&limit=15')
            ->build('GET');

        $response = $this->searchProductionsRequestHandler->handle($request);

        $this->assertEquals(
            [
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 15,
                'totalItems' => 43,
                'member' => [
                    [
                        'production_id' => $productionId->toNative(),
                        'productionId' => $productionId->toNative(),
                        'name' => $name,
                        'events' => $events,
                    ],
                ],
            ],
            Json::decodeAssociatively($response->getBody()->getContents())
        );
    }
}
