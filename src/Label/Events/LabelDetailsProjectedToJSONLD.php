<?php

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Serializer\SerializableInterface;
use ValueObjects\Identity\UUID;

final class LabelDetailsProjectedToJSONLD implements SerializableInterface
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

    public static function deserialize(array $data): LabelDetailsProjectedToJSONLD
    {
        return new static(
            new UUID($data[self::UUID])
        );
    }

    public function serialize(): array
    {
        return [
            self::UUID => $this->getUuid()->toNative(),
        ];
    }
}
