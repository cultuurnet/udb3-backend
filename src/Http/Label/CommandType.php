<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class CommandType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'MakeVisible',
            'MakeInvisible',
            'MakePublic',
            'MakePrivate',
        ];
    }

    public static function makeVisible(): self
    {
        return new CommandType('MakeVisible');
    }

    public static function makeInvisible(): self
    {
        return new CommandType('MakeInvisible');
    }

    public static function makePublic(): self
    {
        return new CommandType('MakePublic');
    }

    public static function makePrivate(): self
    {
        return new CommandType('MakePrivate');
    }
}
