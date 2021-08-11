<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\Request\Body\ContentNegotiationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateSubEventsRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): array
    {
        return (new ContentNegotiationRequestBodyParser())->parse($request);
    }
}
