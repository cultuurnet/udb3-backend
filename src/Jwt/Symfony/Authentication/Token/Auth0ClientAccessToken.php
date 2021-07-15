<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\UserIdentityDetails;

final class Auth0ClientAccessToken extends Auth0AccessToken
{
    public function __construct(string $jwt)
    {
        parent::__construct($jwt);

        if ($this->token->getClaim('gty', '') !== 'client-credentials') {
            throw new InvalidClaims();
        }
    }

    public function getUserId(): string
    {
        return (string) $this->token->getClaim('sub');
    }

    public function getUserIdentityDetails(): ?UserIdentityDetails
    {
        return null;
    }
}
