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
    public function it_returns_the_requested_json_ld_if_found(): void
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
    public function it_includes_metadata_if_the_parameter_is_set_to_true(): void
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
    public function it_does_not_include_metadata_if_the_parameter_is_set_to_false(): void
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
    public function it_returns_url_not_found_if_the_offer_does_not_exist(): void
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
}
