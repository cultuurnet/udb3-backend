<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Uitwisselingsplatform;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Uitwisselingsplatform\Exception\UwpApiFailure;
use CultuurNet\UDB3\Uitwisselingsplatform\Queries\SparqlQueryInterface;
use CultuurNet\UDB3\Uitwisselingsplatform\Queries\VerenigingsloketConnectionQuery;
use CultuurNet\UDB3\Verenigingsloket\Result\VerenigingsloketConnectionResult;
use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class UitwisselingsplatformApiConnector
{
    private ?string $accessToken = null;

    public function __construct(
        private ClientInterface $httpClient,
        private string $clientId,
        private string $clientSecret,
        private LoggerInterface $logger
    ) {
    }

    private function fetchAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $request = new Request(
            'POST',
            'https://auth.uitwisselingsplatform.be/realms/ddt/protocol/openid-connect/token',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => 'profile email openid',
            ])
        );

        try {
            $response = $this->httpClient->sendRequest($request);
            $data = Json::decodeAssociatively($response->getBody()->getContents());

            if (!isset($data['access_token'])) {
                throw new UwpApiFailure('No access token in response');
            }

            $this->accessToken = $data['access_token'];
            return $this->accessToken;
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch access token from UWP', ['exception' => $e->getMessage()]);
            throw new UwpApiFailure('Authentication failed: ' . $e->getMessage());
        }
    }

    private function performSparqlQuery(SparqlQueryInterface $queryObject): ResponseInterface
    {
        $request = new Request(
            'POST',
            $queryObject->getEndpoint(),
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->fetchAccessToken(),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query([
                'query' => $queryObject->getQuery(),
            ])
        );
        return $this->httpClient->sendRequest($request);
    }

    public function fetchVerenigingsloketConnectionForOrganizer(Uuid $organizerId): ?VerenigingsloketConnectionResult
    {
        $response = $this->performSparqlQuery(new VerenigingsloketConnectionQuery($organizerId));

        try {
            $data = JSON::decodeAssociatively($response->getBody()->getContents());
        } catch (\JsonException) {
            return null;
        }

        if (!isset($data['results']['bindings'][0]['vcode']['value'], $data['results']['bindings'][0]['vcode_url']['value'])) {
            return null;
        }

        return new VerenigingsloketConnectionResult(
            $data['results']['bindings'][0]['vcode']['value'],
            $data['results']['bindings'][0]['vcode_url']['value']
        );
    }
}
