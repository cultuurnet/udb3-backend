<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Auth0;

use CultuurNet\UDB3\User\ManagementToken;

class InMemoryRepository implements Auth0ManagementTokenRepository
{
    private ?ManagementToken $token = null;

    public function token(): ?ManagementToken
    {
        return $this->token;
    }

    public function store(ManagementToken $token): void
    {
        $this->token = $token;
    }
}
