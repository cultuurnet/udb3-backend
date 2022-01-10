<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class OfferType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'Event',
            'Place',
        ];
    }

    public static function EVENT(): OfferType
    {
        return new self('Event');
    }

    public static function PLACE(): OfferType
    {
        return new self('Place');
    }

    public static function fromCaseInsensitiveValue($value): OfferType
    {
        return new self(ucfirst(strtolower($value)));
    }
}
