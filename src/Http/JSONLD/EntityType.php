<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\JSONLD;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class EntityType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'event',
            'place',
            'organizer',
            'postaladdress',
        ];
    }

    public static function EVENT(): EntityType
    {
        return new self('event');
    }

    public static function PLACE(): EntityType
    {
        return new self('place');
    }

    public static function ORGANIZER(): EntityType
    {
        return new self('organizer');
    }

    public static function POSTAL_ADDRESS(): EntityType
    {
        return new self('postaladdress');
    }
}
