<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UWP;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\UWP\Exception\UwpApiFailure;
use CultuurNet\UDB3\UWP\Result\VerenigingsloketConnectionResult;
use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class UwpApiConnector
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

    private function performSparqlQuery(string $queryEndpoint, string $query): ResponseInterface
    {
        $request = new Request(
            'POST',
            $queryEndpoint,
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->fetchAccessToken(),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query([
                'query' => $query,
            ])
        );
        return $this->httpClient->sendRequest($request);
    }

    public function fetchVerenigingsloketConnectionForOrganizer(Uuid $organizerId): ?VerenigingsloketConnectionResult
    {
        $orgUrl = 'https://data.publiq.be/id/organizer/udb/' . $organizerId->toString();

        $query = '
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
            PREFIX dcterms: <http://purl.org/dc/terms/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX sssom: <https://w3id.org/sssom/>
            PREFIX adms: <http://www.w3.org/ns/adms#>
            PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

            SELECT DISTINCT ?mapping ?object_id ?object_label ?subject_id ?subject_label ?vcode ?vcode_url
            FROM <https://verenigingsloket.be/mappings/>
            WHERE {
                ?mapping rdf:type sssom:Mapping .
                ?mapping sssom:predicate_id "skos:exactMatch" .
                ?mapping sssom:object_id <' . $orgUrl . '> .
                ?mapping sssom:object_label ?object_label .
                ?mapping sssom:subject_id ?subject_id .
                ?mapping sssom:subject_label ?subject_label .

                # Extract the vcode identifier using string replacement
                BIND(REPLACE(STR(?subject_id), "https://data.vlaanderen.be/id/verenigingen/", "") AS ?vcode)

                # Create the Verenigingsloket URL as a typed literal
                BIND(STRDT(CONCAT("https://www.verenigingsloket.be/nl/verenigingen/", ?vcode), xsd:anyURI) AS ?vcode_url)
            }
            ';

        $response = $this->performSparqlQuery(
            'https://data.uitwisselingsplatform.be/be.dcjm.verenigingen/verenigingen-entity-mapping/sparql',
            $query
        );

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
