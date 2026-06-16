<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Client;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

final class RestUiTPASClient implements UiTPASClient
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly ManagementTokenProvider $tokenProvider,
        private readonly string $apiEndpoint,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getEventCardSystems(string $eventId): array
    {
        $response = $this->client->sendRequest(
            $this->authenticatedRequest('GET', 'events/' . $eventId . '/card-systems')
        );

        if ($response->getStatusCode() === 404) {
            return [];
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->error('UiTPAS REST API returned non-200 status code for event card systems', [
                'event_id' => $eventId,
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);
            return [];
        }

        $cardSystems = [];
        foreach (Json::decodeAssociatively($response->getBody()->getContents()) as $cardSystem) {
            $cardSystems[] = new CardSystem(
                new Id((string) $cardSystem['id']),
                $cardSystem['name']
            );
        }

        return $cardSystems;
    }

    private function authenticatedRequest(string $method, string $path): Request
    {
        return new Request(
            $method,
            $this->apiEndpoint . $path,
            [
                'Authorization' => 'Bearer ' . $this->tokenProvider->token(),
                'Accept' => 'application/json',
            ]
        );
    }
}
