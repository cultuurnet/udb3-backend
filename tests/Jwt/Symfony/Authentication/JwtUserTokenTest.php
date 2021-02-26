<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\Udb3Token;
use Lcobucci\JWT\Token as Jwt;
use PHPUnit\Framework\TestCase;

class JwtUserTokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_jwt_as_credentials()
    {
        $jwt = new Udb3Token(
            new Jwt(
                ['alg' => 'none'],
                [],
                null,
                $payload = ['header', 'payload']
            )
        );

        $jwtUserToken = new JwtUserToken($jwt);

        $this->assertEquals($jwt, $jwtUserToken->getCredentials());
    }

    /**
     * @test
     */
    public function it_can_be_set_as_authenticated()
    {
        $jwtUserToken = new JwtUserToken(new Udb3Token(new Jwt()), true);
        $this->assertTrue($jwtUserToken->isAuthenticated());
    }
}
