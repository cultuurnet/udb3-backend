<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

final class BPostStreetSuggester implements StreetSuggester
{
    private const BPOST_VALIDATION_STREETS = 7;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $domain,
        private readonly string $stage,
        private readonly string $token,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return string[]
     */
    public function suggest(
        string $postalCode,
        string $locality,
        string $streetQuery,
        int $limit = 5
    ): array {
        // @see https://www.bpost.be/en/addressing-web-service-widgets-address-validation
        // for more info about the BPost API.
        // The 'id' parameter depends on the type of validation required
        // (e.g., City, PostalCode, Street or HouseNumber).
        // Use 7 for validating streets.
        $request = new Request(
            'GET',
            (new Uri($this->domain))
                ->withPath('/' . $this->stage . '/externalMailingAddressProofingRest/autocomplete/street')
                ->withQuery(http_build_query([
                'id' => self::BPOST_VALIDATION_STREETS,
                'q' => $streetQuery,
                'postalCode' => $postalCode,
                'locality' => $locality,
                'maxNumberOfSuggestions' => $limit,
            ])),
            [
                'x-api-key' => $this->token,
            ]
        );

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            $this->logger->error('BPost Street Suggester returned non-200 status code', [
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);
            return [];
        }

        return $this->format(Json::decodeAssociatively($response->getBody()->getContents()));
    }

    /**
     * @return string[]
     */
    private function format(array $suggestions): array
    {
        $formattedStreets = [];
        // This is done because the BPost API returns the streets in CAPITALS &
        // with lots of unnecessary information.
        // @see BPostStreetSuggesterTest for an example of the output.
        foreach ($suggestions['response']['topSuggestions'] as $suggestion) {
            $formattedStreets[] = mb_convert_case($suggestion['address']['streetName'], MB_CASE_TITLE, 'UTF-8');
        }
        return $formattedStreets;
    }
}
