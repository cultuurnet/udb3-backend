<?php

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\Identity\UUID;

abstract class AbstractEvent implements SerializableInterface
{
    public const UUID = 'uuid';
    public const NAME = 'name';

    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var LabelName
     */
    private $name;

    public function __construct(UUID $uuid, LabelName $name)
    {
        $this->uuid = $uuid;
        $this->name = $name;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    public function getName(): LabelName
    {
        return $this->name;
    }

    public function serialize(): array
    {
        return [
            self::UUID => $this->getUuid()->toNative(),
            self::NAME => $this->getName()->toNative(),
        ];
    }
}
