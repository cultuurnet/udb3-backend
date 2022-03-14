<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * Does a best effort to convert the old UpdateCalendar JSON schema to the new schema.
 */
final class LegacyUpdateCalendarRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        $data = (new LegacyTimeSpansParser())->parse($data);

        // Convert startDate and endDate on calendarType single to subEvent
        $calendarType = $data->calendarType ?? null;
        if (($calendarType === 'single' || $calendarType === 'multiple') && isset($data->startDate, $data->endDate) && !isset($data->subEvent)) {
            $data->subEvent = [
                (object) [
                    'startDate' => $data->startDate,
                    'endDate' => $data->endDate,
                ],
            ];
        }

        return $request->withParsedBody($data);
    }
}
