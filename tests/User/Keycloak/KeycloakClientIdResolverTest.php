<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\User\ClientIdResolver;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

final class KeycloakClientIdResolverTest extends TestCase
{
    private ClientInterface&MockObject $client;

    protected ClientIdResolver $clientIdResolver;

    public function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->clientIdResolver = new KeycloakClientIdResolver(
            $this->client,
            'http://keycloak',
            'realm',
            'token'
        );
    }

    /**
     * @test
     */
    public function it_can_check_if_a_client_has_entry_access(): void
    {
        $this->client->expects($this->any())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getUri()->getPath() === '/admin/realms/realm/clients/'  &&
                    $request->getMethod() === 'GET' &&
                    $request->getHeaderLine('Authorization') === 'Bearer token';
            }))
            ->willReturn(
                new Response(
                    200,
                    [],
                    Json::encode([
                        0 => [
                            'defaultClientScopes' => ['publiq-api-entry-scope'],
                        ],
                    ])
                )
            );

        $this->assertTrue($this->clientIdResolver->hasEntryAccess('entry_api_key'));
    }

    /**
     * @test
     */
    public function it_can_check_if_a_client_does_not_have_entry_access(): void
    {
        $this->client->expects($this->any())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getUri()->getPath() === '/admin/realms/realm/clients/'  &&
                    $request->getMethod() === 'GET' &&
                    $request->getHeaderLine('Authorization') === 'Bearer token';
            }))
            ->willReturn(
                new Response(
                    200,
                    [],
                    Json::encode([
                        0 => [
                            'defaultClientScopes' => ['publiq-api-search-scope'],
                        ],
                    ])
                )
            );

        $this->assertFalse($this->clientIdResolver->hasEntryAccess('entry_api_key'));
    }
}
