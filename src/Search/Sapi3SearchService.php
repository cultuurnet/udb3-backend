<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifierFactory;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use function http_build_query;

class Sapi3SearchService implements SearchServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private UriInterface $searchLocation;

    private HttpClient $httpClient;

    private ItemIdentifierFactory $itemIdentifierFactory;

    private ?string $apiKey;

    public function __construct(
        UriInterface $searchLocation,
        HttpClient $httpClient,
        ItemIdentifierFactory $itemIdentifierFactory,
        string $apiKey = null
    ) {
        $this->searchLocation = $searchLocation;
        $this->httpClient = $httpClient;
        $this->itemIdentifierFactory = $itemIdentifierFactory;
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

        $itemQuery = $this->searchLocation->withQuery($queryParameters);

        $itemRequest = new Request(
            'GET',
            (string) $itemQuery,
            $headers
        );

        $searchResponseData = $this->httpClient
            ->sendRequest($itemRequest)
            ->getBody()
            ->getContents();

        $this->logger->debug('Sent SAPI3 request with the following parameters: ' . Json::encode($queryParameters));
        $this->logger->debug('Response data: ' . $searchResponseData);

        $searchResponseData = Json::decode($searchResponseData);

        $itemIds = array_reduce(
            $searchResponseData->{'member'},
            fn (ItemIdentifiers $itemIdentifiers, $item) => $itemIdentifiers->with(
                $this->itemIdentifierFactory->fromUrl(new Url($item->{'@id'}))
            ),
            new ItemIdentifiers()
        );

        return new Results($itemIds, (int) $searchResponseData->{'totalItems'});
    }
}
