<?php

namespace CultuurNet\UDB3\Label\ValueObjects;

use ValueObjects\Enum\Enum;

/**
 * Class Visibility
 * @package CultuurNet\UDB3\Label\ValueObjects
 * @method static Visibility VISIBLE()
 * @method static Visibility INVISIBLE()
 */
class Visibility extends Enum
{
    public const VISIBLE = 'visible';
    public const INVISIBLE = 'invisible';
}
