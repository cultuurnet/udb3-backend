<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class KeycloakManagementTokenGeneratorTest extends TestCase
{
    private MockHandler $mockHandler;

    private KeycloakManagementTokenGenerator $tokenGenerator;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $client = new Client(['handler' => HandlerStack::create($this->mockHandler)]);

        $this->tokenGenerator = new KeycloakManagementTokenGenerator(
            $client,
            'https://account-test.uitid.be',
            'master',
            'my-client-id',
            'my-client-secret'
        );
    }

    /**
     * @test
     */
    public function it_fetches_a_client_credentials_token_for_the_configured_realm(): void
    {
        $this->mockHandler->append(
            new Response(200, [], Json::encode(['access_token' => 'token-abc', 'expires_in' => 3600]))
        );

        $token = $this->tokenGenerator->newToken();

        $this->assertEquals('token-abc', $token->getToken());
        $this->assertEquals(3600, $token->getExpiresIn());

        $request = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(
            'https://account-test.uitid.be/realms/master/protocol/openid-connect/token',
            (string) $request->getUri()
        );

        parse_str((string) $request->getBody(), $body);
        $this->assertEquals('client_credentials', $body['grant_type']);
        $this->assertEquals('my-client-id', $body['client_id']);
        $this->assertEquals('my-client-secret', $body['client_secret']);
    }
}
