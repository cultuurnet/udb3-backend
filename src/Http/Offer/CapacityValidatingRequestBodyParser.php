<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rejects a top-level bookingAvailability.capacity for events with a permanent or periodic calendar.
 *
 * A top-level capacity is only meaningful for events that have concrete sub-events, i.e. single or
 * multiple calendars. Places may have a capacity on their permanent/periodic calendars, so this parser
 * must only be wired into event request handlers, never into the shared place handling.
 */
final class CapacityValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!is_object($data)) {
            return $request;
        }

        $calendarType = $data->calendarType ?? null;
        if ($calendarType !== CalendarType::permanent() && $calendarType !== CalendarType::periodic()) {
            return $request;
        }

        if (!isset($data->bookingAvailability) || !is_object($data->bookingAvailability)) {
            return $request;
        }

        if (property_exists($data->bookingAvailability, 'capacity')) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/bookingAvailability/capacity',
                    'capacity is not supported on events with a permanent or periodic calendar.'
                )
            );
        }

        return $request;
    }
}
