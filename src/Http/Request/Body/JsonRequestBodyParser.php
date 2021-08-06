<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;

final class JsonRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): array
    {
        $body = (string) $request->getBody();
        if ($body === '') {
            throw new RequestBodyMissing();
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw RequestBodyInvalidSyntax::invalidJson();
        }

        return $decoded;
    }
}
