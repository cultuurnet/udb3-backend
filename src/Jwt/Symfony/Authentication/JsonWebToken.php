<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use Lcobucci\JWT\Token;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class JsonWebToken extends AbstractToken
{
    /**
     * @var Token
     */
    private $jwt;

    public function __construct(Token $jwt, bool $authenticated = false)
    {
        parent::__construct();
        $this->setAuthenticated($authenticated);
        $this->jwt = $jwt;
    }

    public function getUserId(): string
    {
        if ($this->jwt->hasClaim('uid')) {
            return $this->jwt->getClaim('uid');
        }

        if ($this->jwt->hasClaim('https://publiq.be/uitidv1id')) {
            return $this->jwt->getClaim('https://publiq.be/uitidv1id');
        }

        return $this->jwt->getClaim('sub');
    }

    public function getClientId(): ?string
    {
        // Check first if the token has the claim, to prevent an OutOfBoundsException (thrown if the default is set to
        // null and the claim is missing).
        if ($this->jwt->hasClaim('azp')) {
            return (string) $this->jwt->getClaim('azp');
        }
        return null;
    }

    public function hasClaim(string $name): bool
    {
        return $this->jwt->hasClaim($name);
    }

    /**
     * @return mixed
     */
    public function getClaim(string $name, $default = null)
    {
        return $this->jwt->getClaim($name, $default);
    }

    public function getCredentials(): Token
    {
        return $this->jwt;
    }
}
