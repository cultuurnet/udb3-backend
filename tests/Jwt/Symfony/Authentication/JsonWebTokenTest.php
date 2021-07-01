<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

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
    public function it_can_be_set_as_authenticated(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims([])
            ->authenticate();
        $this->assertTrue($jwt->isAuthenticated());
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
    public function it_returns_v1_jwt_provider_token_type_if_a_uid_claim_is_present(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(['uid' => 'mock']);
        $this->assertEquals(JsonWebToken::V1_JWT_PROVIDER_TOKEN, $jwt->getType());
    }

    /**
     * @test
     */
    public function it_returns_v2_jwt_provider_token_type_if_an_azp_claim_is_missing(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(['sub' => 'auth0|mock-user-id']);
        $this->assertEquals(JsonWebToken::V2_JWT_PROVIDER_TOKEN, $jwt->getType());
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
        $this->assertEquals(JsonWebToken::V2_CLIENT_ACCESS_TOKEN, $jwt->getType());
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
        $this->assertEquals(JsonWebToken::V2_USER_ACCESS_TOKEN, $jwt->getType());
    }
}
