<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;

interface AuthorizedCommandBusInterface extends CommandBus
{
    /**
     * @return bool
     */
    public function isAuthorized(AuthorizableCommandInterface $command);

    /**
     * @return UserIdentificationInterface
     */
    public function getUserIdentification();
}
