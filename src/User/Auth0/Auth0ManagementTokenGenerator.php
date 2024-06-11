<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Auth0;

use CultuurNet\UDB3\User\ManagementToken\ManagementToken;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenGenerator;
use DateTimeImmutable;
use GuzzleHttp\Client;

class Auth0ManagementTokenGenerator implements ManagementTokenGenerator
{
    private Client $client;

    private string $clientId;

    private string $domain;

    private string $clientSecret;

    public function __construct(Client $client, string $clientId, string $domain, string $clientSecret)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->domain = $domain;
        $this->clientSecret = $clientSecret;
    }

    public function newToken(): ManagementToken
    {
        $response = $this->client->post(
            $this->uri(),
            [
                'headers' => ['content-type' => 'application/x-www-form-urlencoded'],
                'body' => $this->body(),
            ]
        );

        $res = json_decode($response->getBody()->getContents(), true);

        return new ManagementToken(
            $res['access_token'],
            new DateTimeImmutable(),
            $res['expires_in']
        );
    }

    private function body(): string
    {
        return sprintf(
            'grant_type=client_credentials&client_id=%s&client_secret=%s&audience=%s',
            $this->clientId,
            $this->clientSecret,
            $this->audience()
        );
    }

    private function uri(): string
    {
        return sprintf('https://%s/oauth/token', $this->domain);
    }

    private function audience(): string
    {
        return sprintf('https://%s/api/v2/', $this->domain);
    }
}
