<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\RDF\JsonToTurtleConverter;
use CultuurNet\UDB3\Http\RDF\TurtleResponseFactory;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepositoryMockFactory;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SampleFiles;
use EasyRdf\Graph;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetDetailRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private OfferJsonDocumentReadRepositoryMockFactory $mockRepositoryFactory;

    private GetDetailRequestHandler $getDetailRequestHandler;

    /** @var JsonToTurtleConverter&MockObject */
    private $placeJsonToTurtleConverter;

    /** @var JsonToTurtleConverter&MockObject */
    private $eventJsonToTurtleConverter;

    protected function setUp(): void
    {
        $this->mockRepositoryFactory = new OfferJsonDocumentReadRepositoryMockFactory();
        $this->placeJsonToTurtleConverter = $this->createMock(JsonToTurtleConverter::class);
        $this->eventJsonToTurtleConverter = $this->createMock(JsonToTurtleConverter::class);

        $this->getDetailRequestHandler = new GetDetailRequestHandler(
            $this->mockRepositoryFactory->create(),
            new TurtleResponseFactory($this->placeJsonToTurtleConverter),
            new TurtleResponseFactory($this->eventJsonToTurtleConverter),
        );
    }

    /**
     * @test
     */
    public function it_returns_the_requested_event_json_ld_if_found(): void
    {
        $this->mockEventDocument('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayNotHasKey('metadata', $decodedResponseBody);
    }

    /**
     * @test
     */
    public function it_returns_the_requested_event_turtle_if_found(): void
    {
        $eventId = 'c09b7a51-b17c-4121-b278-eef71ef04e47';
        $uri = 'https://io.uitdatabank.dev/events/' . $eventId;

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/' . $eventId)
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withHeader('Accept', 'text/turtle')
            ->build('GET');

        $graph = new Graph($uri);
        $resource = $graph->resource($uri);
        $resource->setType('cidoc:E7_Activity');
        $resource->addLiteral('dcterms:title', ['OSLO release party']);
        $turtle = trim((new Turtle())->serialise($graph, 'turtle'));

        $this->eventJsonToTurtleConverter->expects($this->once())
            ->method('convert')
            ->with($eventId)
            ->willReturn($turtle);

        $response = $this->getDetailRequestHandler->handle($request);

        $this->assertEquals(
            SampleFiles::read(__DIR__ . '/samples/event.ttl'),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_returns_the_requested_place_json_ld_if_found(): void
    {
        $this->mockPlaceDocument('fced66fb-72e9-47c3-bde0-7494d299962b');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/fced66fb-72e9-47c3-bde0-7494d299962b')
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', 'fced66fb-72e9-47c3-bde0-7494d299962b')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayNotHasKey('metadata', $decodedResponseBody);
    }

    /**
     * @test
     */
    public function it_returns_the_requested_place_turtle_if_found(): void
    {
        $placeId = 'fced66fb-72e9-47c3-bde0-7494d299962b';
        $uri = 'https://io.uitdatabank.dev/places/' . $placeId;

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/' . $placeId)
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', $placeId)
            ->withHeader('Accept', 'text/turtle')
            ->build('GET');

        $graph = new Graph($uri);
        $resource = $graph->resource($uri);
        $resource->setType('dcterms:Location');
        $resource->addLiteral('locn:locatorName', ['Het Depot']);
        $turtle = trim((new Turtle())->serialise($graph, 'turtle'));

        $this->placeJsonToTurtleConverter->expects($this->once())
            ->method('convert')
            ->with($placeId)
            ->willReturn($turtle);

        $response = $this->getDetailRequestHandler->handle($request);

        $this->assertEquals(
            SampleFiles::read(__DIR__ . '/samples/place.ttl'),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_includes_event_metadata_if_the_parameter_is_set_to_true(): void
    {
        $this->mockEventDocument('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/c09b7a51-b17c-4121-b278-eef71ef04e47?includeMetadata=true')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayHasKey('metadata', $decodedResponseBody);
    }

    /**
     * @test
     */
    public function it_does_not_include_uitpas_prices(): void
    {
        $this->mockEventDocumentWithPriceInfo('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayNotHasKey('metadata', $decodedResponseBody);
        $this->assertEquals(
            [
                [
                    'category' => 'base',
                    'name' => 'Base price',
                    'price' => '10',
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => 'Inwoner Leuven',
                    'price' => '950',
                    'priceCurrency' => 'EUR',
                ],
            ],
            $decodedResponseBody['priceInfo']
        );
    }

    /**
     * @test
     */
    public function it_includes_uitpas_prices_when_enabled(): void
    {
        $this->mockEventDocumentWithPriceInfo('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/c09b7a51-b17c-4121-b278-eef71ef04e47?embedUitpasPrices=true')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayNotHasKey('metadata', $decodedResponseBody);
        $this->assertEquals(
            [
                [
                    'category' => 'base',
                    'name' => 'Base price',
                    'price' => '10',
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => 'Inwoner Leuven',
                    'price' => '950',
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'uitpas',
                    'name' => 'UitPAS Leuven',
                    'price' => '750',
                    'priceCurrency' => 'EUR',
                ],
            ],
            $decodedResponseBody['priceInfo']
        );
    }

    /**
     * @test
     */
    public function it_includes_place_metadata_if_the_parameter_is_set_to_true(): void
    {
        $this->mockPlaceDocument('1ec8604a-000a-4620-8e49-091bb866f773');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/1ec8604a-000a-4620-8e49-091bb866f773?includeMetadata=true')
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', '1ec8604a-000a-4620-8e49-091bb866f773')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayHasKey('metadata', $decodedResponseBody);
    }

    /**
     * @test
     */
    public function it_does_not_include_event_metadata_if_the_parameter_is_set_to_false(): void
    {
        $this->mockEventDocument('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/c09b7a51-b17c-4121-b278-eef71ef04e47?includeMetadata=false')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayNotHasKey('metadata', $decodedResponseBody);
    }

    /**
     * @test
     */
    public function it_does_not_include_place_metadata_if_the_parameter_is_set_to_false(): void
    {
        $this->mockPlaceDocument('f9574fc1-a8d3-4389-8bee-db6dbb6f291e');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/f9574fc1-a8d3-4389-8bee-db6dbb6f291e?includeMetadata=false')
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', 'f9574fc1-a8d3-4389-8bee-db6dbb6f291e')
            ->build('GET');

        $response = $this->getDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayNotHasKey('metadata', $decodedResponseBody);
    }

    private function mockEventDocument(string $eventId): void
    {
        $jsonLd = Json::encode(
            [
                '@id' => '/events/' . $eventId,
                'metadata' => ['foo' => 'bar'],
            ]
        );
        $document = new JsonDocument($eventId, $jsonLd);
        $this->mockRepositoryFactory->expectEventDocument($document);
    }

    private function mockEventDocumentWithPriceInfo(string $eventId): void
    {
        $jsonLd = Json::encode(
            [
                '@id' => '/events/' . $eventId,
                'metadata' => ['foo' => 'bar'],
                'priceInfo'=> [
                    [
                        'category' => 'base',
                        'name' => 'Base price',
                        'price' => '10',
                        'priceCurrency' => 'EUR',
                    ],
                    [
                        'category' => 'tariff',
                        'name' => 'Inwoner Leuven',
                        'price' => '950',
                        'priceCurrency' => 'EUR',
                    ],
                    [
                        'category' => 'uitpas',
                        'name' => 'UitPAS Leuven',
                        'price' => '750',
                        'priceCurrency' => 'EUR',
                    ],
                ],
            ]
        );
        $document = new JsonDocument($eventId, $jsonLd);
        $this->mockRepositoryFactory->expectEventDocument($document);
    }

    private function mockPlaceDocument(string $placeId): void
    {
        $jsonLd = Json::encode(
            [
                '@id' => '/places/' . $placeId,
                'metadata' => ['foo' => 'bar'],
            ]
        );
        $document = new JsonDocument($placeId, $jsonLd);
        $this->mockRepositoryFactory->expectPlaceDocument($document);
    }
}
