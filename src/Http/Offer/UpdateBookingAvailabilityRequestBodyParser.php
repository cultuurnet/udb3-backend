<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\ContentNegotiationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateBookingAvailabilityRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request)
    {
        return (new ContentNegotiationRequestBodyParser())
            ->withJsonRequestBodyParser(
                new JsonSchemaValidatingRequestBodyParser(
                    file_get_contents(__DIR__ . '/../../../vendor/publiq/stoplight-docs-uitdatabank/models/event-bookingAvailability-put.json'),
                    new JsonRequestBodyParser()
                )
            )
            ->parse($request);
    }
}
