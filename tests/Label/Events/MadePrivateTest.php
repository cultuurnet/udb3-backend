<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class MadePrivateTest extends AbstractExtendsTest
{
    public function createEvent(Uuid $uuid, string $name): MadePrivate
    {
        return new MadePrivate($uuid, $name);
    }

    public function deserialize(array $array): MadePrivate
    {
        return MadePrivate::deserialize(
            [
                'uuid' => $this->uuid->toString(),
                'name' => $this->name,
            ]
        );
    }
}
