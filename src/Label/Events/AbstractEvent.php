<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

abstract class AbstractEvent implements Serializable
{
    public const UUID = 'uuid';
    public const NAME = 'name';

    private UUID $uuid;

    private string $name;

    public function __construct(UUID $uuid, string $name)
    {
        $this->uuid = $uuid;
        $this->name = $name;
    }

    public function getUuid(): UUID
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
