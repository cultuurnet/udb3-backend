<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Command;

use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\CommandHandling\AsyncCommandTrait;

final class SendOwnershipRequestedMail implements AsyncCommand
{
    use AsyncCommandTrait;

    private string $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }
}
