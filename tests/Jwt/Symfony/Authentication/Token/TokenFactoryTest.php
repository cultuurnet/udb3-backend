<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use Auth0\SDK\API\Management;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use PHPUnit\Framework\TestCase;

class TokenFactoryTest extends TestCase
{
    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    protected function setUp(): void
    {
        $this->tokenFactory = new TokenFactory(
            new Auth0UserIdentityResolver(
                $this->createMock(Management::class)
            )
        );
    }

    /**
     * @test
     */
    public function it_creates_a_jwt_provider_v1_token_from_a_jwt_provider_v1_token_string(): void
    {
        $token = $this->tokenFactory->createFromJwtString(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => 'ee96acb7-9f65-4fbf-a3a0-f606965300d5',
                    'nick' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $this->assertInstanceOf(JwtProviderV1Token::class, $token);
    }

    /**
     * @test
     */
    public function it_creates_a_jwt_provider_v2_token_from_a_jwt_provider_v2_token_string(): void
    {
        $token = $this->tokenFactory->createFromJwtString(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|ee96acb7-9f65-4fbf-a3a0-f606965300d5',
                    'nickname' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $this->assertInstanceOf(JwtProviderV2Token::class, $token);
    }

    /**
     * @test
     */
    public function it_creates_a_jwt_provider_v2_token_from_a_jwt_provider_v2_token_string_with_v1_id(): void
    {
        $token = $this->tokenFactory->createFromJwtString(
            MockTokenStringFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => 'ee96acb7-9f65-4fbf-a3a0-f606965300d5',
                    'sub' => 'auth0|ee96acb7-9f65-4fbf-a3a0-f606965300d5',
                    'nickname' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $this->assertInstanceOf(JwtProviderV2Token::class, $token);
    }

    /**
     * @test
     */
    public function it_creates_an_auth0_user_access_token_from_an_auth0_user_access_token_string(): void
    {
        $token = $this->tokenFactory->createFromJwtString(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|ee96acb7-9f65-4fbf-a3a0-f606965300d5',
                    'azp' => 'mock-client-id',
                ]
            )
        );

        $this->assertInstanceOf(Auth0UserAccessToken::class, $token);
    }

    /**
     * @test
     */
    public function it_creates_an_auth0_user_access_token_from_an_auth0_user_access_token_string_with_v1_id(): void
    {
        $token = $this->tokenFactory->createFromJwtString(
            MockTokenStringFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => 'ee96acb7-9f65-4fbf-a3a0-f606965300d5',
                    'sub' => 'auth0|ee96acb7-9f65-4fbf-a3a0-f606965300d5',
                    'azp' => 'mock-client-id',
                ]
            )
        );

        $this->assertInstanceOf(Auth0UserAccessToken::class, $token);
    }

    /**
     * @test
     */
    public function it_creates_an_auth0_client_access_token_from_an_auth0_client_access_token_string(): void
    {
        $token = $this->tokenFactory->createFromJwtString(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'mock-client-id@clients',
                    'azp' => 'mock-client-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->assertInstanceOf(Auth0ClientAccessToken::class, $token);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_token_could_not_be_identified(): void
    {
        $this->expectException(InvalidClaims::class);

        $this->tokenFactory->createFromJwtString(
            MockTokenStringFactory::createWithClaims(
                [
                    'foo' => 'bar',
                ]
            )
        );
    }
}
