<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class EventDetailRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private InMemoryDocumentRepository $documentRepository;
    private EventDetailRequestHandler $eventDetailRequestHandler;

    protected function setUp(): void
    {
        $this->documentRepository = new InMemoryDocumentRepository();
        $this->eventDetailRequestHandler = new EventDetailRequestHandler($this->documentRepository);
    }

    /**
     * @test
     */
    public function it_returns_the_requested_event_json_ld_if_found(): void
    {
        $this->mockEventDocument('c09b7a51-b17c-4121-b278-eef71ef04e47');

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/events/c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->withRouteParameter('eventId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->eventDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = json_decode($responseBody, true, JSON_THROW_ON_ERROR);

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
            ->withRouteParameter('eventId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->eventDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = json_decode($responseBody, true, JSON_THROW_ON_ERROR);

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
            ->withRouteParameter('eventId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $response = $this->eventDetailRequestHandler->handle($request);
        $responseBody = $response->getBody()->getContents();
        $decodedResponseBody = json_decode($responseBody, true, JSON_THROW_ON_ERROR);

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
            ->withRouteParameter('eventId', 'c09b7a51-b17c-4121-b278-eef71ef04e47')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::notFound('The event with id "c09b7a51-b17c-4121-b278-eef71ef04e47" was not found.'),
            fn () => $this->eventDetailRequestHandler->handle($request)
        );
    }

    private function mockEventDocument(string $eventId): void
    {
        $jsonLd = json_encode(
            [
                '@id' => '/events/' . $eventId,
                'metadata' => ['foo' => 'bar'],
            ],
            JSON_THROW_ON_ERROR
        );

        $document = new JsonDocument($eventId, $jsonLd);
        $this->documentRepository->save($document);
    }
}
