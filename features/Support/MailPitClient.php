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

    /**
     * @return EmailMessage[]
     */
    public function searchMails(string $query): array
    {
        $response = $this->client->get(
            '/api/v1/search',
            [
                'query' => [
                    'query' => $query,
                ],
            ]
        );
        $messages = Json::decodeAssociatively($response->getBody()->getContents())['messages'];

        $emailMessages = [];
        foreach ($messages as $message) {
            $emailMessages[] = $this->getEmailById($message['ID']);
        }
        return $emailMessages;
    }

    public function getMailCount(): int
    {
        $response = $this->client->get('/api/v1/messages');
        $body = Json::decodeAssociatively($response->getBody()->getContents());
        return $body['messages_count'];
    }

    public function deleteAllMails(): void
    {
        $this->client->delete('/api/v1/messages');
        $elapsedTime = 0;

        do {
            sleep(5);
            $elapsedTime++;
            $mailCount = $this->getMailCount();
        } while ($mailCount !== 0 && $elapsedTime < 5);
    }
}
