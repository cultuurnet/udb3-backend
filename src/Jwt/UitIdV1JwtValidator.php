<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\JsonWebToken;

final class UitIdV1JwtValidator implements JwtValidator
{
    private JwtValidator $baseValidator;

    public function __construct(string $publicKey, array $validIssuers)
    {
        $this->baseValidator = new GenericJwtValidator($publicKey, ['uid'], $validIssuers);
    }

    public function verifySignature(JsonWebToken $token): void
    {
        $this->baseValidator->verifySignature($token);
    }

    public function validateClaims(JsonWebToken $token): void
    {
        $this->baseValidator->validateClaims($token);
    }
}
