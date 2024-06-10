<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Auth0;

class InMemoryRepository implements Auth0ManagementTokenRepository
{
    private ?Auth0Token $token = null;

    public function token(): ?Auth0Token
    {
        return $this->token;
    }

    public function store(Auth0Token $token): void
    {
        $this->token = $token;
    }
}
