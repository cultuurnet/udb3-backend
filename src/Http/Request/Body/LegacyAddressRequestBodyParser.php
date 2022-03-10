<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyAddressRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        $mainLanguage = $data->mainLanguage ?? null;

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

        return $request->withParsedBody($data);
    }
}
