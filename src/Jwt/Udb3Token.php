<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;

/**
 * A wrapper class around the current jwt token to hide away the complexity of determining the correct id based on
 * multiple claims etc.
 */
final class Udb3Token
{
    /**
     * @var Token
     */
    private $token;

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    public function email(): string
    {
        return $this->token->getClaim('email');
    }

    public function id(): string
    {
        if ($this->token->hasClaim('uid')) {
            return $this->token->getClaim('uid');
        }

        if ($this->token->hasClaim('https://publiq.be/uitidv1id')) {
            return $this->token->getClaim('https://publiq.be/uitidv1id');
        }

        return $this->token->getClaim('sub');
    }

    public function jwtToken(): Token
    {
        return $this->token;
    }
}
