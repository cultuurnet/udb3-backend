<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;

/**
 * Class Udb3Token
 * @package CultuurNet\UDB3\Jwt
 *
 * A wrapper class around jwt token
 * to provide fallback functionality for
 * different claim name (a "dirty but
 * works" solution).
 */
class Udb3Token
{
    /**
     * @var Token
     */
    private $token;

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    public function userName(): string
    {
        if ($this->token->hasClaim('nick')) {
            return $this->token->getClaim('nick');
        }

        if ($this->token->hasClaim('nickname')) {
            return $this->token->getClaim('nickname');
        }

        return $this->token->getClaim('email');
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
