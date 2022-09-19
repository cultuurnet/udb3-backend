<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

class QueryValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        if (!isset($request->getParsedBody()->query) || empty($request->getParsedBody()->query)) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (query) are missing'),
            );
        }

        return $request;
    }
}
