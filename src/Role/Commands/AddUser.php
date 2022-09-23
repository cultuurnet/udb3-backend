<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

class AddUser extends AbstractUserCommand
{
    public function __construct(UUID $uuid, string $userId)
    {
        parent::__construct($uuid, new StringLiteral($userId));
    }
}
