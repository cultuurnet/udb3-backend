<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;

interface AuthorizedCommandBusInterface extends CommandBus
{
    public function isAuthorized(AuthorizableCommandInterface $command): bool;

    public function getUserIdentification(): UserIdentificationInterface;

    public function getUserId(): string;
}
