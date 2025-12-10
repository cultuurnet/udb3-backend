<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Verenigingsloket\Enum\VerenigingsloketConnectionStatus;
use CultuurNet\UDB3\Verenigingsloket\Exception\VerenigingsloketApiFailure;
use CultuurNet\UDB3\Verenigingsloket\Result\VerenigingsloketConnectionResult;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Documentation: https://uwp-tni.verenigingsloket.be/api
 */
final class VerenigingsloketApiRepository implements VerenigingsloketConnector
{
    public function __construct(
        private ClientInterface $httpClient,
        private string $websiteUrl,
        private string $apiKey,
    ) {
    }

    private function fetchOrganizerFromVerenigingsloket(Uuid $organizerId): ResponseInterface
    {
        $request = new Request(
            'GET',
            '/api/relations?' .
            http_build_query([
                'organizerId' => $organizerId->toString(),
            ]),
            [
                'Accept' =>  'application/ld+json',
                'X-API-KEY' => $this->apiKey,
            ],
        );

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Exception $e) {
            throw VerenigingsloketApiFailure::apiUnavailable($e->getMessage());
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw VerenigingsloketApiFailure::requestFailed($response->getStatusCode());
        }

        return $response;
    }

    public function fetchVerenigingsloketConnectionForOrganizer(Uuid $organizerId): ?VerenigingsloketConnectionResult
    {
        $response = $this->fetchOrganizerFromVerenigingsloket($organizerId);

        try {
            $data = JSON::decodeAssociatively($response->getBody()->getContents());
        } catch (\JsonException) {
            return null;
        }

        if (empty($data['member'][0]['vCode']) || empty($data['member'][0]['id']) || empty($data['member'][0]['status'])) {
            return null;
        }

        $vCode = $data['member'][0]['vCode'];
        return new VerenigingsloketConnectionResult(
            $vCode,
            $this->websiteUrl . $vCode,
            $data['member'][0]['id'],
            VerenigingsloketConnectionStatus::from($data['member'][0]['status']),
        );
    }

    public function breakConnectionFromVerenigingsloket(Uuid $organizerId, string $userId): bool
    {
        $result = $this->fetchVerenigingsloketConnectionForOrganizer($organizerId);

        if ($result === null) {
            return false;
        }

        $request = new Request(
            'PATCH',
            '/api/relations/' . $result->getRelationId(),
            [
                'Accept' =>  'application/ld+json',
                'Content-Type' => 'application/merge-patch+json' ,
                'X-API-KEY' => $this->apiKey,
            ],
            Json::encode([
                'status' => 'cancelled',
                'initiator' => 'uitdb:' . $userId,
            ])
        );

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Exception $e) {
            throw VerenigingsloketApiFailure::apiUnavailable($e->getMessage());
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw VerenigingsloketApiFailure::requestFailed($response->getStatusCode());
        }

        return true;
    }
}
