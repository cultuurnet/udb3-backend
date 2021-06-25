<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

interface JwtValidatorInterface
{
    /**
     * @throws AuthenticationException
     */
    public function verifySignature(Token $token): void;

    /**
     * @throws AuthenticationException
     */
    public function validateClaims(Token $token): void;
}
