<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

interface CommandBusSecurity
{
    public function isAuthorized(AuthorizableCommand $command): bool;
}
