<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class Auth0UserAccessTokenTest extends TestCase
{
    /**
     * @var UserIdentityResolver|MockObject
     */
    private $userIdentityResolver;

    protected function setUp(): void
    {
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_sub_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims([]),
            $this->userIdentityResolver
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_without_azp_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(['sub' => 'auth0|3bc1185f-4723-4e38-a70c-519d80048f62']),
            $this->userIdentityResolver
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_tokens_with_a_client_credentials_gty_claim(): void
    {
        $this->expectException(InvalidClaims::class);

        new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|3bc1185f-4723-4e38-a70c-519d80048f62',
                    'azp' => 'mock-client-id',
                    'gty' => 'client-credentials',
                ]
            ),
            $this->userIdentityResolver
        );
    }

    /**
     * @test
     */
    public function it_accepts_tokens_with_valid_claims(): void
    {
        new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|3bc1185f-4723-4e38-a70c-519d80048f62',
                    'azp' => 'mock-client-id',
                ]
            ),
            $this->userIdentityResolver
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_returns_the_sub_claim_as_user_id(): void
    {
        $token = new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|3bc1185f-4723-4e38-a70c-519d80048f62',
                    'azp' => 'mock-client-id',
                ]
            ),
            $this->userIdentityResolver
        );

        $this->assertEquals('auth0|3bc1185f-4723-4e38-a70c-519d80048f62', $token->getUserId());
    }

    /**
     * @test
     */
    public function it_returns_the_v1_id_claim_as_user_id_if_present(): void
    {
        $token = new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => '3bc1185f-4723-4e38-a70c-519d80048f62',
                    'sub' => 'auth0|3bc1185f-4723-4e38-a70c-519d80048f62',
                    'azp' => 'mock-client-id',
                ]
            ),
            $this->userIdentityResolver
        );

        $this->assertEquals('3bc1185f-4723-4e38-a70c-519d80048f62', $token->getUserId());
    }

    /**
     * @test
     */
    public function it_fetches_user_identity_details_from_auth0_using_the_sub_claim(): void
    {
        $token = new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => '3bc1185f-4723-4e38-a70c-519d80048f62',
                    'sub' => 'auth0|3bc1185f-4723-4e38-a70c-519d80048f62',
                    'azp' => 'mock-client-id',
                ]
            ),
            $this->userIdentityResolver
        );

        $details = new UserIdentityDetails(
            new StringLiteral('auth0|3bc1185f-4723-4e38-a70c-519d80048f62'),
            new StringLiteral('mock-nick'),
            new EmailAddress('mock@example.com')
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('auth0|3bc1185f-4723-4e38-a70c-519d80048f62'))
            ->willReturn($details);

        $this->assertEquals($details, $token->getUserIdentityDetails());
    }

    /**
     * @test
     */
    public function it_returns_no_user_identity_details_if_auth0_throws_an_exception(): void
    {
        $token = new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => '3bc1185f-4723-4e38-a70c-519d80048f62',
                    'sub' => 'auth0|3bc1185f-4723-4e38-a70c-519d80048f62',
                    'azp' => 'mock-client-id',
                ]
            ),
            $this->userIdentityResolver
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->willThrowException(new Exception('Something went terribly wrong!'));

        $this->assertNull($token->getUserIdentityDetails());
    }
}
