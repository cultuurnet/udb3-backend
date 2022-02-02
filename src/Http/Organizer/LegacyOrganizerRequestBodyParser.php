<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyOrganizerRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        $mainLanguage = $data->mainLanguage ?? null;

        if (isset($data->website) && !isset($data->url)) {
            $data->url = $data->website;
            unset($data->website);
        }

        if ($mainLanguage && isset($data->name) && is_string($data->name)) {
            $data->name = (object) [
                $data->mainLanguage => $data->name,
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

        if (isset($data->contact) && is_array($data->contact)) {
            foreach ($data->contact as $contactEntry) {
                if (!isset($contactEntry->type, $contactEntry->value)) {
                    continue;
                }

                switch ($contactEntry->type) {
                    case 'phone':
                        $phones[] = $contactEntry->value;
                        break;

                    case 'email':
                        $emails[] = $contactEntry->value;
                        break;

                    case 'url':
                        $urls[] = $contactEntry->value;
                        break;
                }
            }

            $data->contactPoint = (object) [
                'phone' => $phones ?? [],
                'email' => $emails ?? [],
                'url' => $urls ?? [],
            ];
        }

        return $request->withParsedBody($data);
    }
}
