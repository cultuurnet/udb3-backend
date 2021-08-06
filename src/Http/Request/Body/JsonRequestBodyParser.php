<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblemException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;
use Psr\Http\Message\ServerRequestInterface;

final class JsonRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): array
    {
        $body = (string) $request->getBody();
        if ($body === '') {
            throw new ApiProblemException(
                ApiProblems::bodyMissing()
            );
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new ApiProblemException(
                ApiProblems::bodyInvalidSyntax('The given request body could not be parsed as JSON.')
            );
        }

        return $decoded;
    }
}
