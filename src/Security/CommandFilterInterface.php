<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;

interface CommandFilterInterface
{
    /**
     * @param AuthorizableCommandInterface $command
     * @return bool
     */
    public function matches(AuthorizableCommandInterface $command);
}
