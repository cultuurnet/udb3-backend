<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

interface JwtValidatorInterface
{
    /**
     * @throws AuthenticationException
     */
    public function verifySignature(Udb3Token $udb3Token): void;

    /**
     * @throws AuthenticationException
     */
    public function validateClaims(Udb3Token $udb3Token): void;

}
