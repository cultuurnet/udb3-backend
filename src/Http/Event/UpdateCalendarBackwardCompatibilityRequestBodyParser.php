<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Does a best effort to convert the old UpdateCalendar JSON schema to the new schema.
 */
final class UpdateCalendarBackwardCompatibilityRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        // Rename timeSpans to subEvent
        if (is_object($data) && isset($data->timeSpans) && is_array($data->timeSpans)) {
            $data->subEvent = array_map(
                function ($timeSpan) {
                    // Rename start to startDate
                    if (is_object($timeSpan) && isset($timeSpan->start)) {
                        $timeSpan->startDate = $timeSpan->start;
                        unset($timeSpan->start);
                    }
                    // Rename end to endDate
                    if (is_object($timeSpan) && isset($timeSpan->end)) {
                        $timeSpan->endDate = $timeSpan->end;
                        unset($timeSpan->end);
                    }
                    return $timeSpan;
                },
                $data->timeSpans
            );
            unset($data->timeSpans);
        }

        return $request;
    }
}
