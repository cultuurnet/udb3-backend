<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var EntityServiceInterface|MockObject */
    private $organizerService;

    private GetOrganizerRequestHandler $getOrganizerRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->organizerService = $this->createMock(EntityServiceInterface::class);
        $this->getOrganizerRequestHandler = new GetOrganizerRequestHandler($this->organizerService);

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
