<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class RemoveUser extends AbstractUserCommand
{
    public function __construct(Uuid $uuid, string $userId)
    {
        parent::__construct($uuid, $userId);
    }
}
