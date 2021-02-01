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
    const VISIBLE = 'visible';
    const INVISIBLE = 'invisible';
}
