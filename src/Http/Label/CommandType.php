<?php

namespace CultuurNet\UDB3\Http\Label;

use ValueObjects\Enum\Enum;

/**
 * Class CommandType
 * @package CultuurNet\UDB3\Http\Label\Helper
 */
class CommandType extends Enum
{
    const MAKE_VISIBLE = 'MakeVisible';
    const MAKE_INVISIBLE = 'MakeInvisible';
    const MAKE_PUBLIC = 'MakePublic';
    const MAKE_PRIVATE = 'MakePrivate';

    public static function MAKE_VISIBLE(): self
    {
        return self::fromNative(self::MAKE_VISIBLE);
    }

    public static function MAKE_INVISIBLE(): self
    {
        return self::fromNative(self::MAKE_INVISIBLE);
    }

    public static function MAKE_PUBLIC(): self
    {
        return self::fromNative(self::MAKE_PUBLIC);
    }

    public static function MAKE_PRIVATE(): self
    {
        return self::fromNative(self::MAKE_PRIVATE);
    }
}
