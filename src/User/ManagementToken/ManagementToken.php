<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

use DateTimeImmutable;

final class ManagementToken
{
    private string $token;

    private DateTimeImmutable $issuedAt;

    private int $expiresIn;

    public function __construct(string $token, DateTimeImmutable $issuedAt, int $expiresIn)
    {
        $this->token = $token;
        $this->issuedAt = $issuedAt;
        $this->expiresIn = $expiresIn;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->issuedAt->modify('+' . $this->expiresIn . 'seconds');
    }
}
