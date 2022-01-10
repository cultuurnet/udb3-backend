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
}
