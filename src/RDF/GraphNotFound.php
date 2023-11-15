<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

final class GraphNotFound extends \Exception
{
    public function __construct(string $uri)
    {
        parent::__construct(sprintf('Graph not found for uri: %s', $uri));
    }
}
