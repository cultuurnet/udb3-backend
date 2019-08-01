<?php

namespace CultuurNet\UDB3\Symfony\Label;

use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Enum\Enum;

/**
 * Class CommandType
 * @package CultuurNet\UDB3\Symfony\Label\Helper
 * @method static CommandType MAKE_VISIBLE
 * @method static CommandType MAKE_INVISIBLE
 * @method static CommandType MAKE_PUBLIC
 * @method static CommandType MAKE_PRIVATE
 */
class CommandType extends Enum
{
    const MAKE_VISIBLE = 'MakeVisible';
    const MAKE_INVISIBLE = 'MakeInvisible';
    const MAKE_PUBLIC = 'MakePublic';
    const MAKE_PRIVATE = 'MakePrivate';
}
