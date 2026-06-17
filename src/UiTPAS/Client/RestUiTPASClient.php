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
        foreach ($this->getEventCardSystemsData($eventId) as $cardSystem) {
            $cardSystems[] = new CardSystem(
                new Id((string) $cardSystem['id']),
                $cardSystem['name']
            );
        }

        return $cardSystems;
    }

    public function addCardSystemToEvent(string $eventId, int $cardSystemId, ?int $distributionKeyId = null): void
    {
        // UiTPAS can only replace the whole list at once, not add one item. So we fetch the current
        // list, switch on the card system we want (and its distribution key) and send the list back.
        $cardSystems = $this->getEventCardSystemsData($eventId);

        $found = false;
        foreach ($cardSystems as &$cardSystem) {
            if ((int) $cardSystem['id'] !== $cardSystemId) {
                continue;
            }

            $found = true;
            $cardSystem['enabled'] = true;

            if ($distributionKeyId !== null) {
                $this->enableDistributionKey($cardSystem, $distributionKeyId);
            }
        }
        unset($cardSystem);

        if (!$found) {
            $newCardSystem = ['id' => $cardSystemId, 'enabled' => true];
            if ($distributionKeyId !== null) {
                $newCardSystem['manualDistributionKeys'] = [['id' => $distributionKeyId, 'enabled' => true]];
            }
            $cardSystems[] = $newCardSystem;
        }

        $this->putEventCardSystems($eventId, $cardSystems);
    }

    /**
     * @param array<string, mixed> $cardSystem
     */
    private function enableDistributionKey(array &$cardSystem, int $distributionKeyId): void
    {
        $cardSystem['manualDistributionKeys'] ??= [];

        $found = false;
        foreach ($cardSystem['manualDistributionKeys'] as &$distributionKey) {
            if ((int) $distributionKey['id'] === $distributionKeyId) {
                $distributionKey['enabled'] = true;
                $found = true;
            }
        }
        unset($distributionKey);

        if (!$found) {
            $cardSystem['manualDistributionKeys'][] = ['id' => $distributionKeyId, 'enabled' => true];
        }
    }

    /**
     * Gets the event's card systems as plain arrays.
     *
     * A 404 means UiTPAS doesn't know this event, so we return an empty list. Any other error throws,
     * so we never accidentally save an empty list back over the real one.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getEventCardSystemsData(string $eventId): array
    {
        $response = $this->client->sendRequest(
            $this->authenticatedRequest('GET', 'events/' . $eventId . '/card-systems')
        );

        if ($response->getStatusCode() === 404) {
            return [];
        }

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
}
