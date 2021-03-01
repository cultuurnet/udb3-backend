<?php

declare(strict_types=1);

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
    public const PRIVACY_PUBLIC = 'public';
    public const PRIVACY_PRIVATE = 'private';
}
