<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class RemainingCapacityValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!is_object($data) || !isset($data->bookingAvailability) || !is_object($data->bookingAvailability)) {
            return $request;
        }

        if (property_exists($data->bookingAvailability, 'remainingCapacity')) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/bookingAvailability/remainingCapacity',
                    'remainingCapacity is not valid on the top-level bookingAvailability.'
                )
            );
        }

        return $request;
    }
}
