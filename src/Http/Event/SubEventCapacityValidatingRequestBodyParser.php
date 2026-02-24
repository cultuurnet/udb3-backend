<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class SubEventCapacityValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $subEvents = $request->getParsedBody();

        $errors = [];
        foreach ($subEvents as $key => $subEvent) {
            if (!is_object($subEvent)) {
                continue;
            }

            $bookingAvailability = isset($subEvent->bookingAvailability) && is_object($subEvent->bookingAvailability)
                ? $subEvent->bookingAvailability
                : null;

            if ($bookingAvailability !== null && isset($bookingAvailability->remainingCapacity, $subEvent->status)) {
                $errors[] = new SchemaError(
                    '/' . $key . '/status',
                    'status and bookingAvailability.remainingCapacity are mutually exclusive'
                );
            }

            if (
                $bookingAvailability !== null &&
                isset($bookingAvailability->remainingCapacity, $bookingAvailability->capacity) &&
                $bookingAvailability->remainingCapacity > $bookingAvailability->capacity
            ) {
                $errors[] = new SchemaError(
                    '/' . $key . '/bookingAvailability/remainingCapacity',
                    'remainingCapacity must be less than or equal to capacity'
                );
            }
        }

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }
}
