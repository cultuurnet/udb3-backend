<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

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
