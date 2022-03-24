<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use DateTime;
use DateTimeInterface;
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
            if (isset($data->calendar->timeSpans) &&
                is_array($data->calendar->timeSpans) &&
                count($data->calendar->timeSpans) > 0) {
                $calendar = (new LegacyTimeSpansParser())->parse($data->calendar);
                $data->subEvent = $calendar->subEvent;
            }

            if (isset($data->calendar->calendarType)) {
                $data->calendarType = $data->calendar->calendarType;
            }

            if (isset($data->calendar->startDate)) {
                $data->startDate = $this->formatDateTime($data->calendar->startDate);
            }

            if (isset($data->calendar->endDate)) {
                $data->endDate = $this->formatDateTime($data->calendar->endDate);
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

        return $request->withParsedBody($data);
    }

    private function formatDateTime(string $dateTime): string
    {
        return (new DateTime($dateTime))->format(DateTimeInterface::ATOM);
    }
}
