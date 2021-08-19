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
     *   Some implementations may require that the given $request already has a getParsedBody() that does not return
     *   null, e.g. if they only do validation or transformations after the actual parsing.
     *
     * @return ServerRequestInterface
     *   New ServerRequestInterface instance that has a getParsedBody() that does not return null.
     *
     * @throws ApiProblem
     */
    public function parse(ServerRequestInterface $request): ServerRequestInterface;
}
