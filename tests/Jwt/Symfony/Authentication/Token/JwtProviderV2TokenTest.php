<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\UserIdentityDetails;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class JwtProviderV2TokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_sub_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims([])
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_nickname_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(['sub' => 'auth0|1745c42f-1d62-4e63-82c5-48c7c3e4eb1b'])
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_email_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nickname' => 'foo',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_accepts_tokens_with_all_required_claims(): void
    {
        new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nickname' => 'foo',
                    'email' => 'mock@example.com',
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
        $token = new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nickname' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $this->assertEquals('auth0|1745c42f-1d62-4e63-82c5-48c7c3e4eb1b', $token->getUserId());
    }

    /**
     * @test
     */
    public function it_returns_the_v1_id_claim_as_user_id_if_present(): void
    {
        $token = new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => '1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'sub' => 'auth0|1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nickname' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $this->assertEquals('1745c42f-1d62-4e63-82c5-48c7c3e4eb1b', $token->getUserId());
    }

    /**
     * @test
     */
    public function it_returns_user_identity_details_based_on_the_claims_in_the_token(): void
    {
        $token = new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => '1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'sub' => 'auth0|1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nickname' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $this->assertEquals(
            new UserIdentityDetails(
                new StringLiteral('1745c42f-1d62-4e63-82c5-48c7c3e4eb1b'),
                new StringLiteral('foo'),
                new EmailAddress('mock@example.com')
            ),
            $token->getUserIdentityDetails()
        );
    }
}
