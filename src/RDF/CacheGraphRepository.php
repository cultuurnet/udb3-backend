<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use Doctrine\Common\Cache\Cache;
use EasyRdf\Graph;

final class CacheGraphRepository implements GraphRepository
{
    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function save(string $uri, Graph $graph): void
    {
        $this->cache->save($uri, trim($graph->serialise('turtle')));
    }

    public function get(string $uri): Graph
    {
        $value = $this->cache->fetch($uri);
        if ($value === false) {
            throw new GraphNotFound($uri);
        }

        $graph = new Graph($uri);
        $graph->parse($value, 'turtle');
        return $graph;
    }
}
