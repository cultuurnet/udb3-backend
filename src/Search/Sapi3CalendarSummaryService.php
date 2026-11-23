<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

final readonly class Sapi3CalendarSummaryService implements CalendarSummaryService
{
    public function __construct(
        private UriInterface $searchLocation,
        private ClientInterface $httpClient,
        private ?string $apiKey
    ) {
    }

    public function get(OfferType $offerType, string $offerId, array $parameters): string
    {
        $path = rtrim($this->searchLocation->getPath(), '/')
            . '/calendar-summary/' . strtolower($offerType->toString()) . 's/' . rawurlencode($offerId);

        $uri = $this->searchLocation
            ->withPath($path)
            ->withQuery(http_build_query($parameters));

        $headers = $this->apiKey ? ['X-Api-Key' => $this->apiKey] : [];

        try {
            $response = $this->httpClient->sendRequest(new Request('GET', $uri, $headers));
        } catch (ClientExceptionInterface $exception) {
            throw new RuntimeException(
                'Could not reach the Search API for the calendar summary of ' . $offerId . ': ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw DocumentDoesNotExist::withId($offerId);
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw new RuntimeException(
                'Search API returned HTTP status ' . $response->getStatusCode() . ' for the calendar summary of ' . $offerId
            );
        }

        return $response->getBody()->getContents();
    }
}
