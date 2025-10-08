<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;

final class BPostStreetSuggester implements StreetSuggester
{
    private ClientInterface $client;

    private string $domain;

    private string $token;

    public function __construct(ClientInterface $client, string $domain, string $token)
    {
        $this->client = $client;
        $this->domain = $domain;
        $this->token = $token;
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
        $request = new Request(
            'GET',
            (new Uri($this->domain))
                ->withPath('/roa-info-st2/externalMailingAddressProofingRest/autocomplete/street')
                ->withQuery(http_build_query([
                'id' => '7',
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
