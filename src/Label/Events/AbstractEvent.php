<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

abstract class AbstractEvent implements Serializable
{
    public const UUID = 'uuid';
    public const NAME = 'name';

    private Uuid $uuid;

    private string $name;

    public function __construct(Uuid $uuid, string $name)
    {
        $this->uuid = $uuid;
        $this->name = $name;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function serialize(): array
    {
        return [
            self::UUID => $this->getUuid()->toString(),
            self::NAME => $this->getName(),
        ];
    }
}
