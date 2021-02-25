<?php

namespace CultuurNet\UDB3\Http\JSONLD;

use ValueObjects\Enum\Enum;

/**
 * @method static EntityType EVENT()
 * @method static EntityType PLACE()
 * @method static EntityType ORGANIZER()
 * @method static EntityType POSTAL_ADDRESS()
 */
class EntityType extends Enum
{
    public const EVENT = 'event';
    public const PLACE = 'place';
    public const ORGANIZER = 'organizer';
    public const POSTAL_ADDRESS = 'postaladdress';
}
