<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyCalendarRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        if (isset($data->calendar) && $data->calendar instanceof stdClass) {
            if (isset($data->calendar->timeSpans)) {
                $calendar = (new LegacyTimeSpansParser())->parse($data->calendar);
                $data->subEvent = $calendar->subEvent;
            }

            if (isset($data->calendar->calendarType)) {
                $data->calendarType = $data->calendar->calendarType;
            }

            if (isset($data->calendar->startDate)) {
                $data->startDate = $data->calendar->startDate;
            }

            if (isset($data->calendar->endDate)) {
                $data->endDate = $data->calendar->endDate;
            }

            if (isset($data->calendar->status)) {
                $data->status = $data->calendar->status;
            }

            if (isset($data->calendar->bookingAvailability)) {
                $data->bookingAvailability = $data->calendar->bookingAvailability;
            }

            if (isset($data->calendar->openingHours)) {
                $data->openingHours = $data->calendar->openingHours;
            }

            unset($data->calendar);
        }

        // Some calendars of type single or multiple don't pass the startDate ane endDate/
        // Those values can be calculated from the subEvent array
        if (!isset($data->startDate, $data->endDate) && isset($data->subEvent)) {
            $data->startDate = $data->subEvent[0]->startDate;
            $data->endDate = $data->subEvent[0]->endDate;

            foreach ($data->subEvent as $subEvent) {
                if ($subEvent->startDate < $data->startDate) {
                    $data->startDate = $subEvent->startDate;
                }

                if ($subEvent->endDate > $data->endDate) {
                    $data->endDate = $subEvent->endDate;
                }
            }
        }

        return $request->withParsedBody($data);
    }
}
