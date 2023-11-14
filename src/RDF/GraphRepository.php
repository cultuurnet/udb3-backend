<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use EasyRdf\Graph;

interface GraphRepository
{
    public function save(string $uri, Graph $graph): void;

    /**
     * @throws GraphNotFound
     */
    public function get(string $uri): Graph;
}
