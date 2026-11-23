<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

final class Sapi3CalendarSummaryService
{
    private UriInterface $searchLocation;
    private ClientInterface $httpClient;
    private ?string $apiKey;

    public function __construct(UriInterface $searchLocation, ClientInterface $httpClient, ?string $apiKey)
    {
        $this->searchLocation = $searchLocation;
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * @param array<string, string> $queryParameters
     * @throws DocumentDoesNotExist
     */
    public function get(OfferType $offerType, string $offerId, array $queryParameters): string
    {
        $path = rtrim($this->searchLocation->getPath(), '/')
            . '/calendar-summary/' . strtolower($offerType->toString()) . 's/' . rawurlencode($offerId);

        $uri = $this->searchLocation
            ->withPath($path)
            ->withQuery(http_build_query($queryParameters));

        $headers = $this->apiKey ? ['X-Api-Key' => $this->apiKey] : [];

        $response = $this->httpClient->sendRequest(new Request('GET', $uri, $headers));

        if ($response->getStatusCode() === 404) {
            throw DocumentDoesNotExist::withId($offerId);
        }

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                'Search API returned HTTP status ' . $response->getStatusCode() . ' for the calendar summary of ' . $offerId
            );
        }

        return $response->getBody()->getContents();
    }
}
