<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class MadeInvisibleTest extends AbstractExtendsTest
{
    public function createEvent(UUID $uuid, string $name): MadeInvisible
    {
        return new MadeInvisible($uuid, $name);
    }

    public function deserialize(array $array): MadeInvisible
    {
        return MadeInvisible::deserialize(
            [
                'uuid' => $this->uuid->toString(),
                'name' => $this->name,
            ]
        );
    }
}
