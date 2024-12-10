<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class MadeVisibleTest extends AbstractExtendsTest
{
    public function createEvent(Uuid $uuid, string $name): MadeVisible
    {
        return new MadeVisible($uuid, $name);
    }

    public function deserialize(array $array): MadeVisible
    {
        return MadeVisible::deserialize(
            [
                'uuid' => $this->uuid->toString(),
                'name' => $this->name,
            ]
        );
    }
}
