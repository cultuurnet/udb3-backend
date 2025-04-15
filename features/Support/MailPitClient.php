<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class MailPitClient implements MailClient
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
            ]
        );
    }

    public function getEmailById(string $messageId): EmailMessage
    {
        {
            $response = $this->client->get(
                '/api/v1/message/' . $messageId,
            );

            return EmailMessage::createFromMailPitData(Json::decodeAssociatively($response->getBody()->getContents()));
        }
    }

    public function getLatestEmail(): EmailMessage
    {
        return $this->getEmailById('latest');
    }
}
