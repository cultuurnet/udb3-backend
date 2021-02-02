<?php

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\Identity\UUID;

final class MadeVisible extends AbstractEvent
{
    public static function deserialize(array $data): MadeVisible
    {
        return new self(
            new UUID($data[self::UUID]),
            new LabelName($data[self::NAME])
        );
    }
}
