<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;

interface AuthorizedCommandBusInterface extends CommandBusInterface
{
    /**
     * @param AuthorizableCommandInterface $command
     * @return bool
     */
    public function isAuthorized(AuthorizableCommandInterface $command);

    /**
     * @return UserIdentificationInterface
     */
    public function getUserIdentification();
}
