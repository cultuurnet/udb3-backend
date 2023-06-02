<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class Included implements Serializable
{
    public const UUID = 'uuid';

    private UUID $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    public function serialize(): array
    {
        return [
            self::UUID => $this->getUuid()->toString(),
        ];
    }

    public static function deserialize(array $data): Included
    {
        return new self(new UUID($data[self::UUID]));
    }
}
