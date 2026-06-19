<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

final class ManagementToken
{
    private string $token;

    private int $expiresIn;

    public function __construct(string $token, int $expiresIn)
    {
        $this->token = $token;
        $this->expiresIn = $expiresIn;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }
}
