<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloack;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\User\ManagementToken\ManagementToken;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenGenerator;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

final class KeycloakManagementTokenGenerator implements ManagementTokenGenerator
{
    private ClientInterface $client;
    private string $domain;
    private string $clientId;
    private string $clientSecret;

    public function __construct(ClientInterface $client, string $domain, string $clientId, string $clientSecret)
    {
        $this->client = $client;
        $this->domain = $domain;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function newToken(): ManagementToken
    {
        $request = new Request(
            'POST',
            $this->domain . '/realms/master/protocol/openid-connect/token',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ])
        );

        $response = $this->client->sendRequest($request);

        $json = Json::decodeAssociatively($response->getBody()->getContents());

        return new ManagementToken(
            $json['access_token'],
            new DateTimeImmutable(),
            $json['expires_in']
        );
    }
}
