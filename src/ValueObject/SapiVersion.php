<?php

namespace CultuurNet\UDB3\ValueObject;

use ValueObjects\Enum\Enum;

/**
 * @method static SapiVersion V2()
 * @method static SapiVersion V3()
 */
class SapiVersion extends Enum
{
    const V2 = 'v2';
    const V3 = 'v3';
}
