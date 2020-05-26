<?php

namespace CultuurNet\UDB3\Search;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class Sapi3CountingSearchServiceTest extends TestCase
{
    /**
     * @var HttpClient|MockObject
     */
    private $httpClient;

    /**
     * @var Sapi3CountingSearchService
     */
    private $searchService;

    /**
     * @var UriInterface
     */
    private $searchLocation;

    public function setUp()
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->searchLocation =  new Uri('http://udb-search.dev/offers/');
        $this->searchService = new Sapi3CountingSearchService($this->searchLocation, $this->httpClient);
    }

    /**
     * @test
     */
    public function it_fetches_the_result_count_from_sapi_3()
    {
        $searchResponse = new Response(200, [], file_get_contents(__DIR__ . '/samples/search-response.json'));

        $expectedRequest = new Request(
            'GET',
            $this->searchLocation->withQuery('q=foo%3Abar&start=0&limit=1')
        );

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($searchResponse);

        $expectedResult = 2;

        $result = $this->searchService->search('foo:bar');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function it_encodes_plus_signs_in_queries()
    {
        $searchResponse = new Response(200, [], file_get_contents(__DIR__ . '/samples/search-response.json'));

        $expectedRequest = new Request(
            'GET',
            $this->searchLocation->withQuery(
                'q=modified%3A%5B2016-08-24T00%3A00%3A00%2B02%3A00+TO+%2A%5D&start=0&limit=1'
            )
        );

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($searchResponse);

        $this->searchService->search('modified:[2016-08-24T00:00:00+02:00 TO *]');
    }

    /**
     * @test
     */
    public function it_can_be_configured_with_additional_query_parameters()
    {
        $searchService = $this->searchService->withQueryParameter('disableDefaultFilters', 'true');

        $searchResponse = new Response(200, [], file_get_contents(__DIR__ . '/samples/search-response.json'));

        $expectedRequest = new Request(
            'GET',
            $this->searchLocation->withQuery(
                'q=foo%3Abar&start=0&limit=1&disableDefaultFilters=true'
            )
        );

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($searchResponse);

        $searchService->search('foo:bar');
    }
}
