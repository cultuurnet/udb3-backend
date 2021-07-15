<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;

final class TokenFactory
{
    /**
     * @var Auth0UserIdentityResolver
     */
    private $auth0UserIdentityResolver;

    public function __construct(Auth0UserIdentityResolver $auth0UserIdentityResolver)
    {
        $this->auth0UserIdentityResolver = $auth0UserIdentityResolver;
    }

    public function createFromJwtString(string $jwt): Token
    {
        try {
            return new JwtProviderV1Token($jwt);
        } catch (InvalidClaims $e) {
        }

        try {
            return new JwtProviderV2Token($jwt);
        } catch (InvalidClaims $e) {
        }

        try {
            return new Auth0UserAccessToken($jwt, $this->auth0UserIdentityResolver);
        } catch (InvalidClaims $e) {
        }

        return new Auth0ClientAccessToken($jwt);
    }
}
