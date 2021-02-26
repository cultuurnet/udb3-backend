<?php

namespace CultuurNet\UDB3\Http\Label;

use ValueObjects\Enum\Enum;

/**
 * Class CommandType
 * @package CultuurNet\UDB3\Http\Label\Helper
 */
class CommandType extends Enum
{
    public const MAKE_VISIBLE = 'MakeVisible';
    public const MAKE_INVISIBLE = 'MakeInvisible';
    public const MAKE_PUBLIC = 'MakePublic';
    public const MAKE_PRIVATE = 'MakePrivate';

    public static function makeVisible(): self
    {
        return self::fromNative(self::MAKE_VISIBLE);
    }

    public static function makeInvisible(): self
    {
        return self::fromNative(self::MAKE_INVISIBLE);
    }

    public static function makePublic(): self
    {
        return self::fromNative(self::MAKE_PUBLIC);
    }

    public static function makePrivate(): self
    {
        return self::fromNative(self::MAKE_PRIVATE);
    }
}
