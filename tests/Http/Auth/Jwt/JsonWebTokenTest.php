<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\TestCase;

class JsonWebTokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_json_web_token_string_as_credentials(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims([]);
        $this->assertTrue(is_string($jwt->getCredentials()));
    }

    /**
     * @test
     */
    public function it_returns_uid_claim_as_id_if_present(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'uid' => '6e3ef9b3-e37b-428e-af30-05f3a96dbbe4',
                'https://publiq.be/uitidv1id' => 'b55f041e-5c5e-4850-9fb8-8cf73d538c56',
                'sub' => 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87',
            ]
        );

        $this->assertEquals('6e3ef9b3-e37b-428e-af30-05f3a96dbbe4', $jwt->getUserId());
    }

    /**
     * @test
     */
    public function it_returns_uitid_v1_claim_as_id_if_present(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'https://publiq.be/uitidv1id' => 'b55f041e-5c5e-4850-9fb8-8cf73d538c56',
                'sub' => 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87',
            ]
        );

        $this->assertEquals('b55f041e-5c5e-4850-9fb8-8cf73d538c56', $jwt->getUserId());
    }

    /**
     * @test
     */
    public function it_returns_sub_claim_as_id(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'sub' => 'auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87',
            ]
        );

        $this->assertEquals('auth0|ce6abd8f-b1e2-4bce-9dde-08af64438e87', $jwt->getUserId());
    }

    /**
     * @test
     */
    public function it_returns_client_id_from_azp_claim_if_present(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'azp' => 'jndYaQY9BSa9W7FQqDEGI0WEi4KlU6vJ',
            ]
        );

        $this->assertEquals('jndYaQY9BSa9W7FQqDEGI0WEi4KlU6vJ', $jwt->getClientId());
    }

    /**
     * @test
     */
    public function it_returns_null_as_client_id_if_azp_claim_is_missing(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims([]);

        $this->assertNull($jwt->getClientId());
    }

    /**
     * @test
     */
    public function it_returns_client_name_from_publiq_client_name_claim_if_present(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'https://publiq.be/client-name' => 'Example',
            ]
        );

        $this->assertEquals('Example', $jwt->getClientName());
    }

    /**
     * @test
     */
    public function it_returns_null_as_client_name_if_publiq_client_name_claim_is_missing(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims([]);

        $this->assertNull($jwt->getClientName());
    }

    /**
     * @test
     */
    public function it_returns_v2_jwt_provider_token_type_when_typ_is_id(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims([
            'azp' => 'mock-client',
            'typ' => 'ID',
        ]);
        $this->assertEquals(JsonWebToken::UIT_ID_V2_JWT_PROVIDER_TOKEN, $jwt->getType());
    }

    /**
     * @test
     */
    public function it_returns_v2_client_access_token_type_if_the_gty_claim_is_set_to_client_credentials(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'sub' => 'mock-client@clients',
                'azp' => 'mock-client',
                'gty' => 'client-credentials',
            ]
        );
        $this->assertEquals(JsonWebToken::UIT_ID_V2_CLIENT_ACCESS_TOKEN, $jwt->getType());
    }

    /**
     * @test
     */
    public function it_returns_v2_user_access_token_type_otherwise(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'sub' => 'auth0|mock-user-id',
                'azp' => 'mock-client',
            ]
        );
        $this->assertEquals(JsonWebToken::UIT_ID_V2_USER_ACCESS_TOKEN, $jwt->getType());
    }

    /**
     * @test
     */
    public function it_returns_user_identity_details_for_v1_jwt_provider_tokens(): void
    {
        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $v1Token = JsonWebTokenFactory::createWithClaims(
            [
                'uid' => 'c82bd40c-1932-4c45-bd5d-a76cc9907cee',
                'nick' => 'mock-nickname',
                'email' => 'mock@example.com',
            ]
        );

        $details = new UserIdentityDetails(
            'c82bd40c-1932-4c45-bd5d-a76cc9907cee',
            'mock-nickname',
            'mock@example.com'
        );

        $this->assertEquals($details, $v1Token->getUserIdentityDetails($userIdentityResolver));
    }

    /**
     * @test
     */
    public function it_can_get_an_email_from_a_token(): void
    {
        $jwtToken = JsonWebTokenFactory::createWithClaims(
            [
                'email' => 'mock@example.com',
            ]
        );

        $OAuth = JsonWebTokenFactory::createWithClaims(
            [
                'https://publiq.be/email' => 'mock@example.com',
            ]
        );

        $tokenWithoutEmail = JsonWebTokenFactory::createWithClaims(
            [
                'sub' => 'auth0|mock-user-id',
                'azp' => 'mock-client',
            ]
        );

        $this->assertEquals(
            new EmailAddress('mock@example.com'),
            $jwtToken->getEmailAddress()
        );

        $this->assertEquals(
            new EmailAddress('mock@example.com'),
            $OAuth->getEmailAddress()
        );

        $this->assertNull($tokenWithoutEmail->getEmailAddress());
    }

    /**
     * @test
     */
    public function it_returns_user_identity_details_for_v2_jwt_provider_tokens(): void
    {
        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $v2Token = JsonWebTokenFactory::createWithClaims(
            [
                'https://publiq.be/uitidv1id' => 'c82bd40c-1932-4c45-bd5d-a76cc9907cee',
                'sub' => 'auth0|c82bd40c-1932-4c45-bd5d-a76cc9907cee',
                'nickname' => 'mock-nickname',
                'email' => 'mock@example.com',
            ]
        );

        $details = new UserIdentityDetails(
            'c82bd40c-1932-4c45-bd5d-a76cc9907cee',
            'mock-nickname',
            'mock@example.com'
        );

        $this->assertEquals($details, $v2Token->getUserIdentityDetails($userIdentityResolver));
    }

    /**
     * @test
     */
    public function it_fetches_user_identity_details_for_user_access_tokens(): void
    {
        $details = new UserIdentityDetails(
            'c82bd40c-1932-4c45-bd5d-a76cc9907cee',
            'mock-nickname',
            'mock@example.com'
        );

        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with('c82bd40c-1932-4c45-bd5d-a76cc9907cee')
            ->willReturn($details);

        $userAccessToken = JsonWebTokenFactory::createWithClaims(
            [
                'https://publiq.be/uitidv1id' => 'c82bd40c-1932-4c45-bd5d-a76cc9907cee',
                'sub' => 'auth0|c82bd40c-1932-4c45-bd5d-a76cc9907cee',
                'azp' => 'mock-client',
            ]
        );

        $this->assertEquals($details, $userAccessToken->getUserIdentityDetails($userIdentityResolver));
    }

    /**
     * @test
     */
    public function it_does_not_return_user_identity_details_for_client_access_tokens(): void
    {
        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $clientAccessToken = JsonWebTokenFactory::createWithClaims(
            [
                'sub' => 'mock-client@clients',
                'azp' => 'mock-client',
                'gty' => 'client-credentials',
            ]
        );

        $this->assertNull($clientAccessToken->getUserIdentityDetails($userIdentityResolver));
    }
}
