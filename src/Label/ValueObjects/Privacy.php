<?php

namespace CultuurNet\UDB3\Label\ValueObjects;

use ValueObjects\Enum\Enum;

/**
 * Class Privacy
 * @package CultuurNet\UDB3\Label\ValueObjects
 * @method static Privacy PRIVACY_PUBLIC()
 * @method static Privacy PRIVACY_PRIVATE()
 */
class Privacy extends Enum
{
    const PRIVACY_PUBLIC = 'public';
    const PRIVACY_PRIVATE = 'private';
}
