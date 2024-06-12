<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Auth0;

use CultuurNet\UDB3\User\ManagementToken;

interface Auth0ManagementTokenRepository
{
    public function token(): ?ManagementToken;

    public function store(ManagementToken $token): void;
}
