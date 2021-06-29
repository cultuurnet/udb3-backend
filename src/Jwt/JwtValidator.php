<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

interface JwtValidator
{
    /**
     * @throws AuthenticationException
     */
    public function verifySignature(JsonWebToken $token): void;

    /**
     * @throws AuthenticationException
     */
    public function validateClaims(JsonWebToken $token): void;
}
