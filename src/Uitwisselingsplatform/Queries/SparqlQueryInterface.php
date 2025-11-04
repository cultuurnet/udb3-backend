<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Uitwisselingsplatform\Queries;

interface SparqlQueryInterface
{
    public function getQuery(): string;

    public function getEndpoint(): string;
}
