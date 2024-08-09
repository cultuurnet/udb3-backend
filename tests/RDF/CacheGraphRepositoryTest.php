<?php

declare(strict_types=1);

namespace RDF;

use CultuurNet\UDB3\RDF\CacheGraphRepository;
use CultuurNet\UDB3\RDF\GraphNotFound;
use CultuurNet\UDB3\SampleFiles;
use Doctrine\Common\Cache\Cache;
use EasyRdf\Graph;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheGraphRepositoryTest extends TestCase
{
    /** @var Cache&MockObject  */
    private Cache $cache;

    private CacheGraphRepository $cacheGraphRepository;

    private string $uri;
    private Graph $graph;

    public function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);
        $this->cacheGraphRepository = new CacheGraphRepository($this->cache);

        $this->uri = 'https://mock.data.publiq.be/events/612e0661-4bb3-42d4-a392-aa5825ca5427';

        $this->graph = new Graph($this->uri);
        $resource = $this->graph->resource($this->uri);
        $resource->setType('cidoc:E7_Activity');
        $resource->addLiteral('dcterms:title', ['My beautiful event']);
    }

    /**
     * @test
     */
    public function it_can_save_a_graph(): void
    {
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->uri, SampleFiles::read(__DIR__ . '/event.ttl'));

        $this->cacheGraphRepository->save($this->uri, $this->graph);
    }

    /**
     * @test
     */
    public function it_can_get_a_graph(): void
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($this->uri)
            ->willReturn(SampleFiles::read(__DIR__ . '/event.ttl'));

        $graph = $this->cacheGraphRepository->get($this->uri);

        $resource = $graph->resource($this->uri);
        $this->assertEquals(
            'cidoc:E7_Activity',
            $resource->type()
        );

        $this->assertEquals(
            'My beautiful event',
            $resource->get('dcterms:title')
        );

        $this->assertEquals(
            $this->graph->resources(),
            $graph->resources()
        );

        // Comparing the full Graph objects only works because `resources()` was first called on both graphs.
        $this->assertEquals(
            $this->graph,
            $graph
        );
    }

    /**
     * @test
     */
    public function it_throws_graph_not_found_when_key_is_not_found(): void
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($this->uri)
            ->willReturn(false);

        $this->expectException(GraphNotFound::class);
        $this->expectExceptionMessage('Graph not found for uri: ' . $this->uri);

        $this->cacheGraphRepository->get($this->uri);
    }
}
