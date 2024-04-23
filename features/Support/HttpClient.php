<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\State\VariableState;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

final class HttpClient
{
    private Client $client;

    public function __construct(
        string $jwt,
        string $apiKey,
        string $clientId,
        string $contentTypeHeader,
        string $acceptHeader,
        string $baseUrl
    ) {
        $headers = [];

        if (!empty($jwt)) {
            $headers['Authorization'] = 'Bearer ' . $jwt;
        }

        if (!empty($apiKey)) {
            $headers['x-api-key'] = $apiKey;
        }

        if (!empty($contentTypeHeader)) {
            $headers['Content-Type'] = $contentTypeHeader;
        }

        if (!empty($acceptHeader)) {
            $headers['Accept'] = $acceptHeader;
        }

        if (!empty($clientId)) {
            $headers['X-Client-Id'] = $clientId;
        }

        $this->client = new Client([
            'base_uri' => $baseUrl,
            'http_errors' => false,
            RequestOptions::HEADERS => $headers,
        ]);
    }

    public function postEmpty(string $url): ResponseInterface
    {
        return $this->client->post($url);
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

    public function putJSON(string $url, string $json): ResponseInterface
    {
        return $this->client->put(
            $url,
            [
                RequestOptions::BODY => $json,
            ]
        );
    }

    public function patchJSON(string $url, string $json): ResponseInterface
    {
        return $this->client->patch(
            $url,
            [
                RequestOptions::BODY => $json,
            ]
        );
    }

    public function get(string $url): ResponseInterface
    {
        return $this->client->get($url);
    }

    public function getWithParameters(string $url, array $parameters, VariableState $variableState): ResponseInterface
    {
        $url .= '?';

        for ($i = 0; $i < count($parameters); $i++) {
            $url .= $parameters[$i][0] . '=' . $parameters[$i][1];
            if ($i < count($parameters) - 1) {
                $url .= '&';
            }
        }

        $url = $variableState->replaceVariables($url);

        return $this->client->get($url);
    }

    public function getWithTimeout(string $url, int $timeout = 5): ResponseInterface
    {
        $elapsedTime = 0;
        do {
            $response = $this->client->get($url);
            if ($response->getStatusCode() !== 200) {
                sleep(1);
                $elapsedTime++;
            }
        } while ($response->getStatusCode() !== 200 && $elapsedTime < $timeout);

        return $response;
    }

    public function delete(string $url): ResponseInterface
    {
        return $this->client->delete($url);
    }

    public function postMultipart(string $url, array $form, string $fileKey, string $filePath): ResponseInterface
    {
        $multipart = [];

        $multipart[] = [
            'name' => $fileKey,
            'contents' => fopen(__DIR__ . '/../data/' . $filePath, 'r'),
        ];

        foreach ($form as $row) {
            $multipart[] = [
                'name' => $row[0],
                'contents' => $row[1],
            ];
        }

        return $this->client->post(
            $url,
            [
                RequestOptions::MULTIPART => $multipart,
            ]
        );
    }
}
