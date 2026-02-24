<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class BookingAvailabilityCapacityValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (
            isset($data->availability, $data->capacity) && $data->availability > $data->capacity
        ) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError('/availability', 'availability must be less than or equal to capacity')
            );
        }

        return $request;
    }
}
