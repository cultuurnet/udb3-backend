<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\Number\Integer;
use ValueObjects\Web\Url;
use function http_build_query;

class Sapi3SearchService implements SearchServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private UriInterface $searchLocation;

    private HttpClient $httpClient;

    private IriOfferIdentifierFactoryInterface $offerIdentifier;

    private ?string $apiKey;

    public function __construct(
        UriInterface $searchLocation,
        HttpClient $httpClient,
        IriOfferIdentifierFactoryInterface $offerIdentifier,
        string $apiKey = null
    ) {
        $this->searchLocation = $searchLocation;
        $this->httpClient = $httpClient;
        $this->offerIdentifier = $offerIdentifier;
        $this->apiKey = $apiKey;
        $this->logger = new NullLogger();
    }

    public function search(string $query, int $limit = 30, int $start = 0, ?array $sort = null): Results
    {
        $queryParameters = [
            'q' => $query,
            'start' => $start,
            'limit' => $limit,
            'disableDefaultFilters' => 'true',
        ];

        if (is_array($sort)) {
            $queryParameters['sort'] = $sort;
        }

        $queryParameters = http_build_query($queryParameters);

        $headers = [];

        if ($this->apiKey) {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        $offerQuery = $this->searchLocation->withQuery($queryParameters);

        $offerRequest = new Request(
            'GET',
            (string) $offerQuery,
            $headers
        );

        $searchResponseData = $this->httpClient
            ->sendRequest($offerRequest)
            ->getBody()
            ->getContents();

        $this->logger->debug('Sent SAPI3 request with the following parameters: ' . Json::encode($queryParameters));
        $this->logger->debug('Response data: ' . $searchResponseData);

        $searchResponseData = Json::decode($searchResponseData);

        $offerIds = array_reduce(
            $searchResponseData->{'member'},
            function (OfferIdentifierCollection $offerIds, $item) {
                return $offerIds->with(
                    $this->offerIdentifier->fromIri(Url::fromNative($item->{'@id'}))
                );
            },
            new OfferIdentifierCollection()
        );

        return new Results($offerIds, new Integer($searchResponseData->{'totalItems'}));
    }
}
