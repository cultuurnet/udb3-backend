<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

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
    public function suggest(string $postalCode, string $locality, string $streetQuery): array
    {
        $request = new Request(
            'GET',
            (new Uri($this->domain))
                ->withPath('/autocomplete/street?id=7&q=' . $streetQuery . '&postalCode=' . $postalCode . '&locality=' . $locality),
            [
                'x-api-key' => $this->token,
            ]
        );

        $response = $this->client->sendRequest($request);

        return $this->format(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * @return string[]
     */
    private function format(array $suggestions): array
    {
        $formattedStreets = [];
        foreach ($suggestions['response']['topSuggestions'] as $suggestion) {
            $formattedStreets[] = mb_convert_case($suggestion['address']['streetName'], MB_CASE_TITLE, 'UTF-8');
        }
        return $formattedStreets;
    }
}
