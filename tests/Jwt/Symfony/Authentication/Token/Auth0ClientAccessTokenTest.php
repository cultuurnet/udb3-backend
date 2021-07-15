<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use PHPUnit\Framework\TestCase;

class Auth0ClientAccessTokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_sub_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims([])
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_azp_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(['sub' => 'mock-client-id@clients'])
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_gty_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'mock-client-id@clients',
                    'azp' => 'mock-client-id',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_with_an_invalid_gty_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'mock-client-id@clients',
                    'azp' => 'mock-client-id',
                    'gty' => 'foo',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_accepts_tokens_with_all_required_claims(): void
    {
        new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'mock-client-id@clients',
                    'azp' => 'mock-client-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_returns_the_sub_claim_as_user_id(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'mock-client-id@clients',
                    'azp' => 'mock-client-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->assertEquals('mock-client-id@clients', $token->getUserId());
    }

    /**
     * @test
     */
    public function it_returns_no_user_details(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'mock-client-id@clients',
                    'azp' => 'mock-client-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->assertNull($token->getUserIdentityDetails());
    }
}
