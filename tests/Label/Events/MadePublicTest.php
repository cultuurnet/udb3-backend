<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class MadePublicTest extends AbstractExtendsTest
{
    public function createEvent(UUID $uuid, string $name): MadePublic
    {
        return new MadePublic($uuid, $name);
    }

    public function deserialize(array $array): MadePublic
    {
        return MadePublic::deserialize(
            [
                'uuid' => $this->uuid->toString(),
                'name' => $this->name,
            ]
        );
    }
}
