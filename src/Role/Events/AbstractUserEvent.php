<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

abstract class AbstractUserEvent extends AbstractEvent
{
    public const USER_ID = 'userId';

    private string $userId;

    final public function __construct(Uuid $uuid, string $userId)
    {
        parent::__construct($uuid);

        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public static function deserialize(array $data): AbstractUserEvent
    {
        return new static(
            new Uuid($data[self::UUID]),
            $data[self::USER_ID]
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [self::USER_ID => $this->getUserId()];
    }
}
