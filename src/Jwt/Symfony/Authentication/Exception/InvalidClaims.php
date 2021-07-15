<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class InvalidClaims extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct('Token has invalid claims.');
    }
}
