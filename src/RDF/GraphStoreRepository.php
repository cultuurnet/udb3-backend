<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use EasyRdf\Graph;
use EasyRdf\GraphStore;

final class GraphStoreRepository implements GraphRepository
{
    private const GRAPH_URI_SUFFIX = '.ttl';
    private GraphStore $graphStore;

    public function __construct(GraphStore $graphStore)
    {
        $this->graphStore = $graphStore;
    }

    public function save(string $uri, Graph $graph): void
    {
        $this->graphStore->replace($graph, $this->appendSuffix($uri));
    }

    public function get(string $uri): Graph
    {
        return $this->graphStore->get($this->appendSuffix($uri));
    }

    private function appendSuffix(string $uri): string
    {
        return $uri . self::GRAPH_URI_SUFFIX;
    }
}
