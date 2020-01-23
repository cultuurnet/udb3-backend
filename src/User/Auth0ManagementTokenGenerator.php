<?php declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use GuzzleHttp\Client;

class Auth0ManagementTokenGenerator
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $clientSecret;

    public function __construct(Client $client, string $clientId, string $domain, string $clientSecret)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->domain = $domain;
        $this->clientSecret = $clientSecret;
    }

    public function newToken(): string
    {
        $response = $this->client->post(
            $this->uri(),
            [
                'headers' => ['content-type' => 'application/x-www-form-urlencoded'],
                'body' => $this->body(),
            ]
        );

        $res = json_decode($response->getBody()->getContents(), true);
        return $res['access_token'];
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
