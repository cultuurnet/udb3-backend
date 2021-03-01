<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use ValueObjects\Enum\Enum;

/**
 * Class AudienceType
 * @package CultuurNet\UDB3\Event\ValueObjects
 *
 * @method static AudienceType EVERYONE()
 * @method static AudienceType MEMBERS()
 * @method static AudienceType EDUCATION()
 */
class AudienceType extends Enum
{
    public const EVERYONE = 'everyone';
    public const MEMBERS = 'members';
    public const EDUCATION = 'education';
}
