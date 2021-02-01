<?php

namespace CultuurNet\UDB3\Role\Events;

use Broadway\Serializer\SerializableInterface;
use ValueObjects\Identity\UUID;

abstract class AbstractEvent implements SerializableInterface
{
    public const UUID = 'uuid';

    /**
     * @var UUID
     */
    private $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    /**
     * @inheritdoc
     */
    public function serialize(): array
    {
        return ['uuid' => $this->getUuid()->toNative()];
    }
}
