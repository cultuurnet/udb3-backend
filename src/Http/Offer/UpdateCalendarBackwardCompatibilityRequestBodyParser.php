<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * Does a best effort to convert the old UpdateCalendar JSON schema to the new schema.
 */
final class UpdateCalendarBackwardCompatibilityRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        // Rename timeSpans to subEvent
        if (isset($data->timeSpans) && is_array($data->timeSpans) && !isset($data->subEvent)) {
            $data->subEvent = array_map(
                function ($timeSpan) {
                    // Rename start to startDate
                    if ($timeSpan instanceof stdClass && isset($timeSpan->start)) {
                        $timeSpan->startDate = $timeSpan->start;
                        unset($timeSpan->start);
                    }
                    // Rename end to endDate
                    if ($timeSpan instanceof stdClass && isset($timeSpan->end)) {
                        $timeSpan->endDate = $timeSpan->end;
                        unset($timeSpan->end);
                    }
                    return $timeSpan;
                },
                $data->timeSpans
            );
            unset($data->timeSpans);
        }

        // Add default status and bookingAvailability to subEvent(s) if missing.
        // Technically they are required on the schema of subEvent as a side effect of using $ref to the subEvent model
        // which has status and bookingAvailability set to required because they are always present on the read model.
        // For backward compatibility we need to set defaults when they are missing so the validator does not complain.
        // This is also the same behaviour as when we used the old CalendarJSONParser.
        $defaultStatusType = isset($data->status, $data->status->type) ? $data->status->type : 'Available';
        $defaultStatusReason = isset($data->status, $data->status->reason) ? $data->status->reason : null;
        $defaultBookingAvailabilityType = isset($data->bookingAvailability, $data->bookingAvailability->type) ? $data->bookingAvailability->type : 'Available';
        if (isset($data->subEvent) && is_array($data->subEvent)) {
            $data->subEvent = array_map(
                function ($subEvent) use ($defaultStatusType, $defaultStatusReason, $defaultBookingAvailabilityType) {
                    if ($subEvent instanceof stdClass &&
                        !isset($subEvent->status)) {
                        $subEvent->status = (object) [];
                    }
                    if (isset($subEvent->status) &&
                        is_object($subEvent->status) &&
                        !isset($subEvent->status->type)) {
                        $subEvent->status->type = $defaultStatusType;
                    }
                    if (isset($subEvent->status) &&
                        is_object($subEvent->status) &&
                        !isset($subEvent->status->reason) &&
                        !is_null($defaultStatusReason)) {
                        $subEvent->status->reason = $defaultStatusReason;
                    }

                    if ($subEvent instanceof stdClass &&
                        !isset($subEvent->bookingAvailability)) {
                        $subEvent->bookingAvailability = (object) [];
                    }
                    if (isset($subEvent->bookingAvailability) &&
                        is_object($subEvent->bookingAvailability) &&
                        !isset($subEvent->bookingAvailability->type)) {
                        $subEvent->bookingAvailability->type = $defaultBookingAvailabilityType;
                    }

                    return $subEvent;
                },
                $data->subEvent
            );
        }

        return $request->withParsedBody($data);
    }
}
