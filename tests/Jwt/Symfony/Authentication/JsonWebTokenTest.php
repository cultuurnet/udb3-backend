<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\Udb3Token;
use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token as Jwt;
use PHPUnit\Framework\TestCase;

class JsonWebTokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_udb3_token_as_credentials(): void
    {
        $udb3Token = new Udb3Token(
            new Jwt(
                ['alg' => 'none'],
                [],
                null,
                $payload = ['header', 'payload']
            )
        );

        $jwt = new JsonWebToken($udb3Token);

        $this->assertEquals($udb3Token, $jwt->getCredentials());
    }

    /**
     * @test
     */
    public function it_can_be_set_as_authenticated(): void
    {
        $jwt = new JsonWebToken(new Udb3Token(new Jwt()), true);
        $this->assertTrue($jwt->isAuthenticated());
    }

    /**
     * @test
     */
    public function it_returns_uid_claim_as_id_if_present(): void
    {
        $udb3Token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'uid' => new Basic('uid', '6e3ef9b3-e37b-428e-af30-05f3a96dbbe4'),
                    'https://publiq.be/uitidv1id' => new Basic(
                        'https://publiq.be/uitidv1id',
                        'b55f041e-5c5e-4850-9fb8-8cf73d538c56'
                    ),
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $jwt = new JsonWebToken($udb3Token);

        $this->assertEquals('6e3ef9b3-e37b-428e-af30-05f3a96dbbe4', $jwt->id());
    }

    /**
     * @test
     */
    public function it_returns_uitid_v1_claim_as_id_if_present(): void
    {
        $udb3Token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'https://publiq.be/uitidv1id' => new Basic(
                        'https://publiq.be/uitidv1id',
                        'b55f041e-5c5e-4850-9fb8-8cf73d538c56'
                    ),
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $jwt = new JsonWebToken($udb3Token);

        $this->assertEquals('b55f041e-5c5e-4850-9fb8-8cf73d538c56', $jwt->id());
    }

    /**
     * @test
     */
    public function it_returns_sub_claim_as_id(): void
    {
        $udb3Token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $jwt = new JsonWebToken($udb3Token);

        $this->assertEquals('auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87', $jwt->id());
    }

    /**
     * @test
     */
    public function it_returns_client_id_from_azp_claim_if_present(): void
    {
        $udb3Token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'azp' => new Basic('azp', 'jndYaQY9BSa9W7FQqDEGI0WEi4KlU6vJ'),
                ]
            )
        );

        $jwt = new JsonWebToken($udb3Token);

        $this->assertEquals('jndYaQY9BSa9W7FQqDEGI0WEi4KlU6vJ', $jwt->getClientId());
    }

    /**
     * @test
     */
    public function it_returns_null_as_client_id_if_azp_claim_is_missing(): void
    {
        $udb3Token = new Udb3Token(
            new Token(
                ['alg' => 'none'],
                [
                    'sub' => new Basic('sub', 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87'),
                ]
            )
        );

        $jwt = new JsonWebToken($udb3Token);

        $this->assertNull($jwt->getClientId());
    }
}
