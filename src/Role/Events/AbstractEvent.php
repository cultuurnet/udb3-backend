<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

abstract class AbstractEvent implements Serializable
{
    public const UUID = 'uuid';

    private Uuid $uuid;

    public function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function serialize(): array
    {
        return ['uuid' => $this->getUuid()->toString()];
    }
}
