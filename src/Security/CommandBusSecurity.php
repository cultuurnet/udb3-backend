<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

interface CommandBusSecurity
{
    /**
     * Returns if the event allows updates through the UDB3 core APIs.
     *
     * @return bool
     */
    public function isAuthorized(AuthorizableCommandInterface $command);
}
