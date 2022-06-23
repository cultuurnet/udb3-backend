<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class MadePrivate extends AbstractEvent
{
    public static function deserialize(array $data): MadePrivate
    {
        return new self(
            new UUID($data[self::UUID]),
            $data[self::NAME]
        );
    }
}
