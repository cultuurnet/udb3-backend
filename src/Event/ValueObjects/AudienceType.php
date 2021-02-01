<?php

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
    const EVERYONE = 'everyone';
    const MEMBERS = 'members';
    const EDUCATION = 'education';
}
