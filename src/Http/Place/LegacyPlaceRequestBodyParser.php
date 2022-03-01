<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyPlaceRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        $mainLanguage = $data->mainLanguage ?? null;

        if ($mainLanguage && isset($data->name) && is_string($data->name)) {
            $data->name = (object) [
                $data->mainLanguage => $data->name,
            ];
        }

        if (isset($data->type) && $data->type instanceof stdClass) {
            $terms = [
                'id' => $data->type->id,
            ];

            if (isset($data->type->label)) {
                $terms['label'] = $data->type->label;
            }

            if (isset($data->type->domain)) {
                $terms['domain'] = $data->type->domain;
            }

            $data->terms = [
                (object) $terms,
            ];
        }

        if ($mainLanguage && isset($data->address->streetAddress)) {
            $data->address->{$mainLanguage} = $data->address->{$mainLanguage} ?? (object) [];
            $data->address->{$mainLanguage}->streetAddress = $data->address->streetAddress;
            unset($data->address->streetAddress);
        }
        if ($mainLanguage && isset($data->address->postalCode)) {
            $data->address->{$mainLanguage} = $data->address->{$mainLanguage} ?? (object) [];
            $data->address->{$mainLanguage}->postalCode = $data->address->postalCode;
            unset($data->address->postalCode);
        }
        if ($mainLanguage && isset($data->address->addressLocality)) {
            $data->address->{$mainLanguage} = $data->address->{$mainLanguage} ?? (object) [];
            $data->address->{$mainLanguage}->addressLocality = $data->address->addressLocality;
            unset($data->address->addressLocality);
        }
        if ($mainLanguage && isset($data->address->addressCountry)) {
            $data->address->{$mainLanguage} = $data->address->{$mainLanguage} ?? (object) [];
            $data->address->{$mainLanguage}->addressCountry = $data->address->addressCountry;
            unset($data->address->addressCountry);
        }

        if (isset($data->calendar) && $data->calendar instanceof stdClass) {
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
        }

        return $request->withParsedBody($data);
    }
}
