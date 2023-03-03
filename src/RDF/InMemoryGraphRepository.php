<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use EasyRdf\Graph;
use EasyRdf\Serialiser\Turtle;

final class InMemoryGraphRepository implements GraphRepository
{
    private array $graphs = [];

    public function save(string $uri, Graph $graph): void
    {
        $this->graphs[$uri] = $graph;
    }

    public function get(string $uri): Graph
    {
        return $this->graphs[$uri] ?? new Graph($uri);
    }
}
