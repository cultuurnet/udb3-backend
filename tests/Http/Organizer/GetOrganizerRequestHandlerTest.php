<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\RDF\JsonToTurtleConverter;
use CultuurNet\UDB3\Http\RDF\TurtleResponseFactory;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use EasyRdf\Graph;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var EntityServiceInterface|MockObject */
    private $organizerService;

    private GetOrganizerRequestHandler $getOrganizerRequestHandler;

    /** @var JsonToTurtleConverter&MockObject */
    private $jsonToTurtleConverter;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->organizerService = $this->createMock(EntityServiceInterface::class);
        $this->jsonToTurtleConverter = $this->createMock(JsonToTurtleConverter::class);

        $this->getOrganizerRequestHandler = new GetOrganizerRequestHandler(
            $this->organizerService,
            new TurtleResponseFactory(
                $this->jsonToTurtleConverter
            )
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_getting_an_organizer(): void
    {
        $organizerId = 'a088f396-ac96-45c4-b6b2-e2b6afe8af07';

        $getOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $organizerId)
            ->build('GET');

        $this->organizerService->expects($this->once())
            ->method('getEntity')
            ->with($organizerId)
            ->willReturn('{"id":"a088f396-ac96-45c4-b6b2-e2b6afe8af07"}');

        $response = $this->getOrganizerRequestHandler->handle($getOrganizerRequest);

        $this->assertEquals(
            '{"id":"a088f396-ac96-45c4-b6b2-e2b6afe8af07"}',
            $response->getBody()
        );
    }

    /**
     * @test
     */
    public function it_handles_getting_an_organizer_as_turtle(): void
    {
        $organizerId = 'a088f396-ac96-45c4-b6b2-e2b6afe8af07';
        $uri = 'https://io.uitdatabank.dev/organizers/' . $organizerId;

        $getOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $organizerId)
            ->withHeader('Accept', 'text/turtle')
            ->build('GET');

        $this->organizerService->expects($this->never())
            ->method('getEntity');

        $graph = new Graph();
        $resource = $graph->resource($uri);
        $resource->setType('cp:Organisator');
        $resource->addLiteral('cpr:naam', ['publiq vzw']);
        $turtle = trim((new Turtle())->serialise($graph, 'turtle'));

        $this->jsonToTurtleConverter->expects($this->once())
            ->method('convert')
            ->with($organizerId)
            ->willReturn($turtle);

        $response = $this->getOrganizerRequestHandler->handle($getOrganizerRequest);

        $this->assertEquals(
            file_get_contents(__DIR__ . '/samples/organizer.ttl'),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_when_organizer_not_found(): void
    {
        $organizerId = 'a088f396-ac96-45c4-b6b2-e2b6afe8af07';

        $getOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $organizerId)
            ->build('GET');

        $this->organizerService->expects($this->once())
            ->method('getEntity')
            ->with($organizerId)
            ->willThrowException(new EntityNotFoundException());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::organizerNotFound($organizerId),
            fn () => $this->getOrganizerRequestHandler->handle($getOrganizerRequest)
        );
    }
}
