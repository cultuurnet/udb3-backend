<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepositoryMockFactory;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class GetDetailRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private OfferJsonDocumentReadRepositoryMockFactory $mockRepositoryFactory;
    private GetDetailRequestHandler $getDetailRequestHandler;

    protected function setUp(): void
    {
        $this->mockRepositoryFactory = new OfferJsonDocumentReadRepositoryMockFactory();
        $this->getDetailRequestHandler = new GetDetailRequestHandler($this->mockRepositoryFactory->create());
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

    /**
     * @test
     */
    public function it_returns_url_not_found_if_the_event_does_not_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::eventNotFound('c09b7a51-b17c-4121-b278-eef71ef04e47'),
            fn () => $this->getDetailRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_returns_url_not_found_if_the_place_does_not_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/1e960233-b724-4c56-89dc-c160d15508c6')
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', '1e960233-b724-4c56-89dc-c160d15508c6')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::placeNotFound('1e960233-b724-4c56-89dc-c160d15508c6'),
            fn () => $this->getDetailRequestHandler->handle($request)
        );
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
