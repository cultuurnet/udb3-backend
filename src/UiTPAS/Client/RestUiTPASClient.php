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
use RuntimeException;

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
        $cardSystems = [];
        foreach ($this->getEventCardSystemsData($eventId) as $cardSystemData) {
            $cardSystems[] = new CardSystem(
                new Id((string) $cardSystemData['id']),
                $cardSystemData['name']
            );
        }

        return $cardSystems;
    }

    public function addCardSystemToEvent(string $eventId, int $cardSystemId, ?int $distributionKeyId = null): void
    {
        // UiTPAS only supports replacing the whole list, so fetch it, switch our card system on and put it back.
        $cardSystems = $this->getEventCardSystemsData($eventId);

        $found = false;
        foreach ($cardSystems as $index => $cardSystem) {
            if ((int) $cardSystem['id'] !== $cardSystemId) {
                continue;
            }

            $found = true;
            $cardSystems[$index]['enabled'] = true;

            if ($distributionKeyId !== null) {
                $cardSystems[$index] = $this->enableDistributionKey($cardSystems[$index], $distributionKeyId);
            }
        }

        if (!$found) {
            $newCardSystem = ['id' => $cardSystemId, 'enabled' => true];
            if ($distributionKeyId !== null) {
                $newCardSystem['manualDistributionKeys'] = [['id' => $distributionKeyId, 'enabled' => true]];
            }
            $cardSystems[] = $newCardSystem;
        }

        $this->putEventCardSystems($eventId, $cardSystems);
    }

    public function deleteCardSystemFromEvent(string $eventId, int $cardSystemId): void
    {
        // Same as adding, but switch our card system off.
        $cardSystems = $this->getEventCardSystemsData($eventId);

        foreach ($cardSystems as $index => $cardSystem) {
            if ((int) $cardSystem['id'] === $cardSystemId) {
                $cardSystems[$index]['enabled'] = false;
            }
        }

        $this->putEventCardSystems($eventId, $cardSystems);
    }

    public function setCardSystemsForEvent(string $eventId, array $cardSystemIds): void
    {
        // Enable exactly the given card systems and disable the rest.
        $cardSystems = $this->getEventCardSystemsData($eventId);

        foreach ($cardSystems as $index => $cardSystem) {
            $cardSystems[$index]['enabled'] = in_array((int) $cardSystem['id'], $cardSystemIds, true);
        }

        $existingIds = array_map(static fn (array $cardSystem): int => (int) $cardSystem['id'], $cardSystems);
        foreach (array_diff($cardSystemIds, $existingIds) as $cardSystemId) {
            $cardSystems[] = ['id' => $cardSystemId, 'enabled' => true];
        }

        $this->putEventCardSystems($eventId, $cardSystems);
    }

    public function eventHasTicketSales(string $eventId): bool
    {
        // limit=0 returns no items, just the total count.
        $response = $this->client->sendRequest(
            $this->authenticatedRequest('GET', 'ticket-sales?eventId=' . rawurlencode($eventId) . '&limit=0')
        );

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                'UiTPAS REST API returned status code ' . $response->getStatusCode()
                . ' for ticket sales: ' . $response->getBody()->getContents()
            );
        }

        return (Json::decodeAssociatively($response->getBody()->getContents())['totalItems'] ?? 0) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEventCardSystemsData(string $eventId): array
    {
        $response = $this->client->sendRequest(
            $this->authenticatedRequest('GET', 'events/' . $eventId . '/card-systems')
        );

        // UiTPAS doesn't know this event, so there are no card systems.
        if ($response->getStatusCode() === 404) {
            return [];
        }

        // Throw on other errors so we never overwrite the real list with an empty one.
        if ($response->getStatusCode() !== 200) {
            $body = $response->getBody()->getContents();
            $this->logger->error('UiTPAS REST API returned non-200 status code for event card systems', [
                'event_id' => $eventId,
                'status_code' => $response->getStatusCode(),
                'body' => $body,
            ]);
            throw new RuntimeException(
                'UiTPAS REST API returned status code ' . $response->getStatusCode()
                . ' for event card systems: ' . $body
            );
        }

        return Json::decodeAssociatively($response->getBody()->getContents());
    }

    /**
     * @param array<int, array<string, mixed>> $cardSystems
     */
    private function putEventCardSystems(string $eventId, array $cardSystems): void
    {
        $response = $this->client->sendRequest(
            $this->authenticatedRequest(
                'PUT',
                'events/' . $eventId . '/card-systems',
                Json::encode(array_values($cardSystems))
            )
        );

        if ($response->getStatusCode() !== 204) {
            throw new RuntimeException(
                'UiTPAS REST API returned status code ' . $response->getStatusCode()
                . ' while updating event card systems: ' . $response->getBody()->getContents()
            );
        }
    }

    private function authenticatedRequest(string $method, string $path, ?string $body = null): Request
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->tokenProvider->token(),
            'Accept' => 'application/json',
        ];

        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        return new Request($method, $this->apiEndpoint . $path, $headers, $body);
    }

    /**
     * @param array<string, mixed> $cardSystem
     * @return array<string, mixed>
     */
    private function enableDistributionKey(array $cardSystem, int $distributionKeyId): array
    {
        $cardSystem['manualDistributionKeys'] ??= [];

        $found = false;
        foreach ($cardSystem['manualDistributionKeys'] as $index => $distributionKey) {
            if ((int) $distributionKey['id'] === $distributionKeyId) {
                $cardSystem['manualDistributionKeys'][$index]['enabled'] = true;
                $found = true;
            }
        }

        if (!$found) {
            $cardSystem['manualDistributionKeys'][] = ['id' => $distributionKeyId, 'enabled' => true];
        }

        return $cardSystem;
    }
}
