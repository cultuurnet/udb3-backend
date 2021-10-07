<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class GetPlaceDetailRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private InMemoryDocumentRepository $documentRepository;
    private GetPlaceDetailRequestHandler $getPlaceDetailRequestHandler;

    protected function setUp(): void
    {
        $this->documentRepository = new InMemoryDocumentRepository();
        $this->getPlaceDetailRequestHandler = new GetPlaceDetailRequestHandler($this->documentRepository);
    }

    /**
     * @test
     */
    public function it_returns_the_requested_place_json_ld_if_found(): void
    {
        $this->mockPlaceDocument('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->withRouteParameter('placeId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getPlaceDetailRequestHandler->handle($request);
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
        $this->mockPlaceDocument('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/c09b7a51-b17c-4121-b278-eef71ef04e47?includeMetadata=true')
            ->withRouteParameter('placeId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getPlaceDetailRequestHandler->handle($request);
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
        $this->mockPlaceDocument('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/c09b7a51-b17c-4121-b278-eef71ef04e47?includeMetadata=false')
            ->withRouteParameter('placeId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->getPlaceDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = Json::decodeAssociatively($responseBody);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('@id', $decodedResponseBody);
        $this->assertArrayNotHasKey('metadata', $decodedResponseBody);
    }

    /**
     * @test
     */
    public function it_returns_url_not_found_if_the_place_does_not_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places/c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->withRouteParameter('placeId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::notFound('The place with id "c09b7a51-b17c-4121-b278-eef71ef04e47" was not found.'),
            fn () => $this->getPlaceDetailRequestHandler->handle($request)
        );
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
        $this->documentRepository->save($document);
    }
}
