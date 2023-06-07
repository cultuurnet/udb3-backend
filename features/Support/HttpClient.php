<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

final class HttpClient
{
    private Client $client;

    function __construct(
        string $jwt,
        string $apiKey,
        string $contentTypeHeader,
        string $acceptHeader,
        string $baseUrl
    ){
        $headers = [
            'Authorization' => 'Bearer ' . $jwt,
            'x-api-key' => $apiKey,
            'Content-Type' => $contentTypeHeader,
            'Accept' => $acceptHeader,
        ];

        $this->client = new Client([
            'base_uri' => $baseUrl,
            RequestOptions::HEADERS => $headers,
        ]);
    }

    public function postJSON(string $url, string $json): ResponseInterface
    {
        return $this->client->post(
            $url,
            [
                RequestOptions::BODY => $json,
            ]
        );
    }

    public function getJSON(string $url): string
    {
        $response = $this->client->get($url);

        return $response->getBody()->getContents();
    }
}