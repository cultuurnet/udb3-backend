<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class Excluded implements Serializable
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
        return [
            self::UUID => $this->getUuid()->toString(),
        ];
    }

    public static function deserialize(array $data): Excluded
    {
        return new self(new Uuid($data[self::UUID]));
    }
}
