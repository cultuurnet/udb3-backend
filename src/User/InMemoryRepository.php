<?php declare(strict_types=1);

namespace CultuurNet\UDB3\User;

class InMemoryRepository implements Auth0ManagementTokenRepository
{

    private $token = null;

    public function token(): ?string
    {
        return $this->token;
    }

    public function store(string $token): void
    {
        $this->token = $token;
    }
}
