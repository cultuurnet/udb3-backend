<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private DocumentRepository&MockObject $organizerRepository;

    private GetOrganizerRequestHandler $getOrganizerRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->organizerRepository = $this->createMock(DocumentRepository::class);

        $this->getOrganizerRequestHandler = new GetOrganizerRequestHandler(
            $this->organizerRepository
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

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(
                new JsonDocument('a088f396-ac96-45c4-b6b2-e2b6afe8af07', '{"id":"a088f396-ac96-45c4-b6b2-e2b6afe8af07"}')
            );

        $response = $this->getOrganizerRequestHandler->handle($getOrganizerRequest);

        $this->assertEquals(
            '{"id":"a088f396-ac96-45c4-b6b2-e2b6afe8af07"}',
            $response->getBody()
        );
    }

    /**
     * @test
     */
    public function it_returns_json_ld_when_turtle_is_requested(): void
    {
        $organizerId = 'a088f396-ac96-45c4-b6b2-e2b6afe8af07';

        $getOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $organizerId)
            ->withHeader('Accept', 'text/turtle')
            ->build('GET');

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(
                new JsonDocument($organizerId, '{"id":"a088f396-ac96-45c4-b6b2-e2b6afe8af07"}')
            );

        $response = $this->getOrganizerRequestHandler->handle($getOrganizerRequest);

        $this->assertEquals('application/ld+json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            '{"id":"a088f396-ac96-45c4-b6b2-e2b6afe8af07"}',
            $response->getBody()
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

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willThrowException(new DocumentDoesNotExist());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::organizerNotFound($organizerId),
            fn () => $this->getOrganizerRequestHandler->handle($getOrganizerRequest)
        );
    }
}
