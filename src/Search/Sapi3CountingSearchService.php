<?php

namespace CultuurNet\UDB3\Search;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use function http_build_query;
use Psr\Http\Message\UriInterface;

class Sapi3CountingSearchService implements CountingSearchServiceInterface
{
    /**
     * @var UriInterface
     */
    private $searchLocation;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string|null
     */
    private $apiKey;

    /**
     * @var array
     */
    private $queryParameters;

    /**
     * @param UriInterface $searchLocation
     * @param HttpClient $httpClient
     * @param string|null $apiKey
     */
    public function __construct(
        UriInterface $searchLocation,
        HttpClient $httpClient,
        string $apiKey = null
    ) {
        $this->searchLocation = $searchLocation;
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->queryParameters = [];
    }

    public function withQueryParameter(string $key, $value)
    {
        $c = clone $this;
        $c->queryParameters[$key] = $value;
        return $c;
    }

    /**
     * @inheritdoc
     */
    public function search(string $query): int
    {
        $queryParameters =
            [
                'q' => $query,
                'start' => 0,
                'limit' => 1,
            ];

        $queryParameters += $this->queryParameters;

        $queryParameters = http_build_query($queryParameters);

        $headers = [];

        if ($this->apiKey) {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        $url = $this->searchLocation->withQuery($queryParameters);

        $request = new Request(
            'GET',
            (string) $url,
            $headers
        );

        $response = $this->httpClient->sendRequest($request);

        $decodedResponse = json_decode(
            $response->getBody()->getContents()
        );

        return (int) $decodedResponse->{'totalItems'};
    }
}
