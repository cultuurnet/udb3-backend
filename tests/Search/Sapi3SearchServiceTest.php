<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\OfferType;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use ValueObjects\Number\Integer;
use ValueObjects\Web\Url;

class Sapi3SearchServiceTest extends TestCase
{
    /**
     * @var HttpClient|MockObject
     */
    private $httpClient;

    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    private $offerIdentifier;

    /**
     * @var Sapi3SearchService
     */
    private $searchService;

    /**
     * @var UriInterface
     */
    private $searchLocation;

    public function setUp()
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->offerIdentifier = new IriOfferIdentifierFactory(
            'https?://udb-silex\.dev/(?<offertype>[event|place]+)/(?<offerid>[a-zA-Z0-9\-]+)'
        );
        $this->searchLocation =  new Uri('http://udb-search.dev/offers/');
        $this->searchService = new Sapi3SearchService($this->searchLocation, $this->httpClient, $this->offerIdentifier);
    }

    /**
     * @test
     */
    public function it_should_fetch_search_results_from_sapi_3()
    {
        $searchResponse = new Response(200, [], file_get_contents(__DIR__ . '/samples/search-response.json'));

        $expectedRequest = new Request(
            'GET',
            $this->searchLocation->withQuery('q=foo%3Abar&start=0&limit=30&disableDefaultFilters=true')
        );

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($searchResponse);

        $expectedResults = new Results(
            OfferIdentifierCollection::fromArray([
                new IriOfferIdentifier(
                    Url::fromNative('http://udb-silex.dev/place/c90bc8d5-11c5-4ae3-9bf9-cce0969fdc56'),
                    'c90bc8d5-11c5-4ae3-9bf9-cce0969fdc56',
                    OfferType::PLACE()
                ),
                new IriOfferIdentifier(
                    Url::fromNative('http://udb-silex.dev/event/c54b1323-0928-402f-9419-16d7acd44d36'),
                    'c54b1323-0928-402f-9419-16d7acd44d36',
                    OfferType::EVENT()
                ),
            ]),
            Integer::fromNative(2)
        );

        $results = $this->searchService->search('foo:bar');

        $this->assertEquals($expectedResults, $results);
    }

    /**
     * @test
     */
    public function it_should_properly_encode_plus_signs_in_queries()
    {
        $searchResponse = new Response(200, [], file_get_contents(__DIR__ . '/samples/search-response.json'));

        $expectedRequest = new Request(
            'GET',
            $this->searchLocation->withQuery(
                'q=modified%3A%5B2016-08-24T00%3A00%3A00%2B02%3A00+TO+%2A%5D&start=0&limit=30&disableDefaultFilters=true'
            )
        );

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($searchResponse);

        $this->searchService->search('modified:[2016-08-24T00:00:00+02:00 TO *]');
    }
}
