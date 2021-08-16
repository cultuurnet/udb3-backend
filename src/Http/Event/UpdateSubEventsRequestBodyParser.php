<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\Request\Body\ContentNegotiationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateSubEventsRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request)
    {
        return (new ContentNegotiationRequestBodyParser())
            ->withJsonRequestBodyParser(
                new JsonRequestBodyParser(file_get_contents(__DIR__ . '/UpdateSubEventsSchema.json'))
            )
            ->parse($request);
    }
}
