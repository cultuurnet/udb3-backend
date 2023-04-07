<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use EasyRdf\Graph;

final class InMemoryGraphRepository implements GraphRepository
{
    private array $graphs = [];

    public function save(string $uri, Graph $graph): void
    {
        $this->graphs[$uri] = $graph;
    }

    public function get(string $uri): Graph
    {
        if (isset($this->graphs[$uri])) {
            // Return a clone, so that the graph is not updated in this repository without calling save() because it is
            // updated by reference if it is not cloned first.
            // Clone the object by serializing and unserializing it, so that is "deep" cloned, i.e. it's nested objects
            // are also cloned.
            return unserialize(serialize($this->graphs[$uri]));
        }
        return new Graph($uri);
    }
}
