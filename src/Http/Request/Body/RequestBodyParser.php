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
     * @return mixed
     *   The decoded data as an array (if a list) or an stdClass (if an object) or a scalar type.
     *   For optimal compatibility with decorators the data should not yet be converted to a value object.
     *
     * @throws ApiProblem
     */
    public function parse(ServerRequestInterface $request);
}
