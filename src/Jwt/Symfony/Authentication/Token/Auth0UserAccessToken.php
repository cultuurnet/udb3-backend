<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Exception;
use ValueObjects\StringLiteral\StringLiteral;

final class Auth0UserAccessToken extends Auth0AccessToken
{
    /**
     * @var UserIdentityResolver
     */
    private $userIdentityResolver;

    public function __construct(string $jwt, UserIdentityResolver $userIdentityResolver)
    {
        parent::__construct($jwt);
        $this->userIdentityResolver = $userIdentityResolver;

        if ($this->token->getClaim('gty', '') === 'client-credentials') {
            throw new InvalidClaims();
        }
    }

    public function getUserId(): string
    {
        if ($this->token->hasClaim('https://publiq.be/uitidv1id')) {
            return (string) $this->token->getClaim('https://publiq.be/uitidv1id');
        }

        return (string) $this->token->getClaim('sub');
    }

    public function getUserIdentityDetails(): ?UserIdentityDetails
    {
        $sub = $this->token->getClaim('sub');

        try {
            return $this->userIdentityResolver->getUserById(new StringLiteral($sub));
        } catch (Exception $e) {
            return null;
        }
    }
}
