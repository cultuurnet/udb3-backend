<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Security\AuthorizableCommand;

interface AuthorizedCommandBusInterface extends CommandBus
{
    public function isAuthorized(AuthorizableCommand $command): bool;

    public function getUserId(): string;

    public function disableAuthorization(): void;

    public function enableAuthorization(): void;
}
