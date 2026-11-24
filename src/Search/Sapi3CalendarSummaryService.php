<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
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
        $path = '/' . $this->pluralType($offerType) . '/' . rawurlencode($offerId) . '/calendar-summary';

        $accept = ($parameters['style'] ?? 'text') === 'html' ? 'text/html' : 'text/plain';
        unset($parameters['style']);

        $response = $this->send(
            $path,
            http_build_query($parameters),
            'the calendar summary of ' . $offerId,
            ['Accept' => $accept]
        );

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw DocumentDoesNotExist::withId($offerId);
        }

        $this->guardOk($response, 'the calendar summary of ' . $offerId);

        return $response->getBody()->getContents();
    }

    public function getMany(OfferType $offerType, array $offerIds, array $parameters): array
    {
        if ($offerIds === []) {
            return [];
        }

        $language = $parameters['language'] ?? 'nl';
        $style = $parameters['style'] ?? 'text';
        $size = $parameters['size'] ?? 'lg';

        // The embedded summaries are rendered by the search results, so one search gets them all.
        $query = http_build_query([
            'q' => 'id:(' . implode(' OR ', $offerIds) . ')',
            'limit' => count($offerIds),
            'disableDefaultFilters' => 'true',
            'embedCalendarSummaries' => [$size . '-' . $style],
            'embedCalendarSummariesHidePast' => $parameters['hidePast'] ?? 'false',
        ]);

        $context = 'the calendar summaries of ' . count($offerIds) . ' ' . $this->pluralType($offerType);
        $response = $this->send('/' . $this->pluralType($offerType), $query, $context);
        $this->guardOk($response, $context);

        $summaries = [];
        foreach (Json::decodeAssociatively($response->getBody()->getContents())['member'] ?? [] as $member) {
            $summary = $member['calendarSummary'][$language][$style][$size] ?? null;
            if (is_string($summary)) {
                $summaries[basename($member['@id'])] = $summary;
            }
        }
        return $summaries;
    }

    /**
     * @param array<string, string> $headers
     */
    private function send(string $path, string $query, string $context, array $headers = []): ResponseInterface
    {
        $uri = $this->searchLocation
            ->withPath(rtrim($this->searchLocation->getPath(), '/') . $path)
            ->withQuery($query);

        if ($this->apiKey) {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        try {
            return $this->httpClient->sendRequest(new Request('GET', $uri, $headers));
        } catch (ClientExceptionInterface $exception) {
            throw new RuntimeException(
                'Could not reach the Search API for ' . $context . ': ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function guardOk(ResponseInterface $response, string $context): void
    {
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw new RuntimeException(
                'Search API returned HTTP status ' . $response->getStatusCode() . ' for ' . $context
            );
        }
    }

    private function pluralType(OfferType $offerType): string
    {
        return strtolower($offerType->toString()) . 's';
    }
}
