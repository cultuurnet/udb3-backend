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

        // Rename timeSpans to subEvent
        if (isset($data->timeSpans) && is_array($data->timeSpans)) {
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
        // If we didn't set these defaults and made the properties optional in the schema, they would still get these
        // defaults down the line anyway.
        if (isset($data->subEvent) && is_array($data->subEvent)) {
            $data->subEvent = array_map(
                function ($subEvent) {
                    if ($subEvent instanceof stdClass && !isset($subEvent->status)) {
                        $subEvent->status = (object) ['type' => 'Available'];
                    }
                    if ($subEvent instanceof stdClass && !isset($subEvent->bookingAvailability)) {
                        $subEvent->bookingAvailability = (object) ['type' => 'Available'];
                    }
                    return $subEvent;
                },
                $data->subEvent
            );
        }

        return $request;
    }
}
