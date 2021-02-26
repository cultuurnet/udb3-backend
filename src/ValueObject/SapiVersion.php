<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ValueObject;

use ValueObjects\Enum\Enum;

/**
 * @method static SapiVersion V2()
 * @method static SapiVersion V3()
 */
class SapiVersion extends Enum
{
    public const V2 = 'v2';
    public const V3 = 'v3';
}
