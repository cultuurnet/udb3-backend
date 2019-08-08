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
    const EVENT = 'event';
    const PLACE = 'place';
    const ORGANIZER = 'organizer';
    const POSTAL_ADDRESS = 'postaladdress';
}
