<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

final class JsonRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request)
    {
        $body = (string) $request->getBody();
        if ($body === '') {
            throw ApiProblem::bodyMissing();
        }

        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ApiProblem::bodyInvalidSyntax('JSON');
        }

        return $decoded;
    }
}
