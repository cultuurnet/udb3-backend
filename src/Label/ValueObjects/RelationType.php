<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class RelationType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'Event',
            'Place',
            'Organizer',
        ];
    }

    public static function event(): RelationType
    {
        return new self('Event');
    }

    public static function place(): RelationType
    {
        return new self('Place');
    }

    public static function organizer(): RelationType
    {
        return new self('Organizer');
    }
}
