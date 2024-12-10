<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

abstract class AbstractUserCommand extends AbstractCommand
{
    private string $userId;

    public function __construct(
        Uuid $uuid,
        string $userId
    ) {
        parent::__construct($uuid);
        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
