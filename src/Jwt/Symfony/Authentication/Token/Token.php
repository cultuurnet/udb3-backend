<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\User\UserIdentityDetails;

interface Token
{
    public function getUserId(): string;
    public function getUserIdentityDetails(): ?UserIdentityDetails;

    public function isUsableAtCurrentTime(): bool;
    public function hasValidIssuer(array $validIssuers): bool;
    public function hasAudience(string $audience): bool;
    public function verifyRsaSha256Signature(string $publicKey, ?string $keyPassphrase = null): bool;
}
