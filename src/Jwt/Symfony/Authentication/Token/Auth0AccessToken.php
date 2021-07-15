<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;

abstract class Auth0AccessToken extends AbstractToken implements AccessToken
{
    public function __construct(string $jwt)
    {
        parent::__construct($jwt);

        if (!$this->hasClaims(['sub', 'azp'])) {
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

    public function getClientId(): string
    {
        return (string) $this->token->getClaim('azp');
    }

    public function canUseEntryApi(): bool
    {
        $apis = $this->token->getClaim('https://publiq.be/publiq-apis', '');

        if (!is_string($apis)) {
            return false;
        }

        $apis = explode(' ', $apis);
        return in_array('entry', $apis, true);
    }
}
