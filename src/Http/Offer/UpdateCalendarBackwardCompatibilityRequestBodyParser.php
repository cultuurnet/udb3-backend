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

        // Convert startDate and endDate on calendarType single to subEvent
        $calendarType = $data->calendarType ?? null;
        if ($calendarType === 'single' && isset($data->startDate, $data->endDate) && !isset($data->subEvent)) {
            $data->subEvent = [
                (object) [
                    'startDate' => $data->startDate,
                    'endDate' => $data->endDate,
                ]
            ];
        }

        return $request->withParsedBody($data);
    }
}
