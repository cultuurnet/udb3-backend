<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Security\AuthorizableCommandInterface;

interface AuthorizedCommandBusInterface extends CommandBus
{
    public function isAuthorized(AuthorizableCommandInterface $command): bool;

    public function getUserId(): string;
}
