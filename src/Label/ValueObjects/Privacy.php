<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

//use ValueObjects\Enum\Enum;
use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * Class Privacy
 * @package CultuurNet\UDB3\Label\ValueObjects
 * @method static Privacy public()
 * @method static Privacy private()
 */
class Privacy extends Enum
{
    public const PRIVACY_PUBLIC = 'public';
    public const PRIVACY_PRIVATE = 'private';
}
