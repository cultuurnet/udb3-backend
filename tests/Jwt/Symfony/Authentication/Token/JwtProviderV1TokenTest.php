<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\UserIdentityDetails;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class JwtProviderV1TokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_uid_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims([])
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_nick_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(['uid' => '1745c42f-1d62-4e63-82c5-48c7c3e4eb1b'])
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_email_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => '1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nick' => 'foo',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_accepts_tokens_with_all_required_claims(): void
    {
        new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => '1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nick' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_returns_the_uid_claim_as_user_id(): void
    {
        $token = new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => '1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nick' => 'foo',
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
        $token = new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => '1745c42f-1d62-4e63-82c5-48c7c3e4eb1b',
                    'nick' => 'foo',
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
