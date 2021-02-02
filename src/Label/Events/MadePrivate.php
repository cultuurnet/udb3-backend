<?php

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\Identity\UUID;

class MadePrivate extends AbstractEvent
{
    public static function deserialize(array $data): MadePrivate
    {
        return new self(
            new UUID($data[self::UUID]),
            new LabelName($data[self::NAME])
        );
    }
}
