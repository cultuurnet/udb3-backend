<?php

namespace CultuurNet\UDB3\RDF;

use EasyRdf\Graph;

interface GraphRepository
{
    public function save(string $uri, Graph $graph): void;
    public function get(string $uri): Graph;
}
