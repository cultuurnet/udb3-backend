<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\Udb3Token;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class JwtUserToken extends AbstractToken
{
    /**
     * @var Udb3Token
     */
    private $jwt;

    public function __construct(Udb3Token $jwt, bool $authenticated = false)
    {
        parent::__construct();
        $this->setAuthenticated($authenticated);
        $this->jwt = $jwt;
    }

    public function getCredentials(): Udb3Token
    {
        return $this->jwt;
    }
}
