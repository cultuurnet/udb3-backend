<?php

namespace CultuurNet\UDB3\Role\Events;

use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractUserEvent extends AbstractEvent
{
    public const USER_ID = 'userId';

    /**
     * @var StringLiteral
     */
    private $userId;

    final public function __construct(UUID $uuid, StringLiteral $userId)
    {
        parent::__construct($uuid);

        $this->userId = $userId;
    }

    public function getUserId(): StringLiteral
    {
        return $this->userId;
    }

    public static function deserialize(array $data): AbstractUserEvent
    {
        return new static(
            new UUID($data[self::UUID]),
            new StringLiteral($data[self::USER_ID])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [self::USER_ID => $this->getUserId()->toNative()];
    }
}
