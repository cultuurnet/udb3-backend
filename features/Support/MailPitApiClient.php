<?php

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

final class MailPitApiClient
{
    private Client $client;

    public function __construct(string $baseUrl)
    {
        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';

        $this->client = new Client(
            [
                'base_uri' => $baseUrl,
                'http_errors' => false,
                RequestOptions::HEADERS => $headers,
            ]);
    }

    public function get(string $messageId = 'latest'): ResponseInterface
    {
        {
            return $this->client->get(
                '/api/v1/message/' . $messageId,
            );
        }
    }

    public function search(string $query): ResponseInterface
    {
        {
            return $this->client->get(
                '/api/v1/messages/search',
                [
                    RequestOptions::QUERY => $query,
                ]
            );
        }
    }

    /**
     * @param string[] $messageIds
     */
    public function delete(array $messageIds): ResponseInterface
    {
        return $this->client->delete(
            '/api/v1/messages',
            [
                RequestOptions::BODY => $this->formatMessageIds($messageIds)
            ]
        );
    }

    /**
     * @param string[] $messageIds
     */
    private function formatMessageIds(array $messageIds): string
    {
        return Json::encode(['IDs' => $messageIds]);
    }
}