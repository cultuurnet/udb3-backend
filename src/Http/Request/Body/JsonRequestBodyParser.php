<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Json;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

final class JsonRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $body = (string) $request->getBody();
        if ($body === '') {
            throw ApiProblem::bodyMissing();
        }

        try {
            $decoded = Json::decode($body);
        } catch (JsonException $e) {
            throw ApiProblem::bodyInvalidSyntax('JSON');
        }

        try {
            return $request->withParsedBody($decoded);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::bodyInvalidData(new SchemaError('/', 'Root element must be an array or object'));
        }
    }
}
