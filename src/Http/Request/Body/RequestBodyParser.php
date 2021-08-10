<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Psr\Http\Message\ServerRequestInterface;

interface RequestBodyParser
{
    /**
     * Parses and validates a request's content.
     *
     * @param ServerRequestInterface $request
     *   The incoming (PSR-7) request of which the body has to be parsed.
     *
     * @throws ApiProblem
     *
     * @return array
     *   The decoded data as an associative array.
     */
    public function parse(ServerRequestInterface $request): array;
}
