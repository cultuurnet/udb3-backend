<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\MovieFetcher;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;

final class MovieService
{
    private string $movieApiBaseUrl;

    private string $key;

    private string $secret;

    private ClientInterface $client;

    public function __construct(
        string $movieApiBaseUrl,
        ClientInterface $client,
        string $key,
        string $secret
    ) {
        $this->movieApiBaseUrl = $movieApiBaseUrl;
        $this->client = $client;
        $this->key = $key;
        $this->secret = $secret;
    }

    public function getToken(): string
    {
        $request = new Request(
            'POST',
            $this->movieApiBaseUrl . '/services/jwt/1.0/token',
            $this->getHeaders(),
            Json::encode([
                'client' => $this->key,
                'secret' => $this->secret,
            ])
        );
        $response = $this->client->sendRequest($request)->getBody()->getContents();
        $contents = Json::decode($response);
        return $contents['token'];
    }

    public function getMovies(string $token): array
    {
        $request = new Request(
            'GET',
            $this->movieApiBaseUrl . '/services/content/1.1/movies?progList=2',
            $this->getHeaders($token)
        );

        $response = $this->client->sendRequest($request)->getBody()->getContents();
        return Json::decode($response);
    }

    public function getMovieDetail(string $token, string $mid): array
    {
        $request = new Request(
            'GET',
            $this->movieApiBaseUrl . 'services/content/1.1/movies/' . $mid,
            $this->getHeaders($token)
        );

        $response = $this->client->sendRequest($request)->getBody()->getContents();
        return Json::decode($response);

    }

    private function getHeaders(string $token = null): array
    {
        $headers = [
            'content-type' => 'application/json',
            'User-Agent' => 'Kinepolis-Publiq',
        ];
        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }
}
