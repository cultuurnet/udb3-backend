<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtValidator;

final class JwtAuthenticationProvider
{
    /**
     * @var JwtValidator
     */
    private $v1JwtValidator;

    /**
     * @var JwtValidator
     */
    private $v2JwtValidator;

    public function __construct(
        JwtValidator $v1JwtValidator,
        JwtValidator $v2JwtValidator
    ) {
        $this->v1JwtValidator = $v1JwtValidator;
        $this->v2JwtValidator = $v2JwtValidator;
    }

    public function authenticate(JsonWebToken $token): void
    {
        $isV1 = $token->getType() === JsonWebToken::V1_JWT_PROVIDER_TOKEN;
        $validator = $isV1 ? $this->v1JwtValidator : $this->v2JwtValidator;

        $validator->verifySignature($token);
        $validator->validateClaims($token);
    }
}
