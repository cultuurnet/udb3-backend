<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;

interface CommandFilterInterface
{
    /**
     * @return bool
     */
    public function matches(AuthorizableCommandInterface $command);
}
