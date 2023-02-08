<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private UUID $organizerId;

    private UpdateContributorsRequestHandler $updateContributorsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    /**
     * @var OrganizerRepository|MockObject
     */
    private $organizerRepository;

    public function setUp(): void
    {
        $this->organizerId = new UUID('4c47cbf8-8406-4af6-b6e7-fddd78e0efd8');
        $this->organizerRepository = $this->createMock(OrganizerRepository::class);
        $this->updateContributorsRequestHandler = new UpdateContributorsRequestHandler(
            $this->organizerRepository,
            $this->createMock(ContributorRepository::class)
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_updating_contributors(): void
    {
        $updateContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId->toString())
            ->withJsonBodyFromArray(
                [
                    'jan@gent.be',
                    'piet@gent.be',
                    'an@gent.be',
                ]
            )
            ->build('PUT');

        $response = $this->updateContributorsRequestHandler->handle($updateContributorsRequest);

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     */
    public function it_handles_invalid_emails(): void
    {
        $invalidContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId->toString())
            ->withJsonBodyFromArray(
                [
                    'piet@gent.be',
                    'an@gent.be',
                    '09/1231212',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/2', 'The data must match the \'email\' format')
            ),
            fn () => $this->updateContributorsRequestHandler->handle($invalidContributorsRequest)
        );
    }

    /**
     * @test
     */
    public function it_handles_unknown_organizer(): void
    {
        $this->organizerRepository->expects($this->once())
            ->method('load')
            ->with($this->organizerId->toString())
            ->willThrowException(new AggregateNotFoundException());

        $unkownOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId->toString())
            ->withJsonBodyFromArray(
                [
                    'piet@gent.be',
                    'an@gent.be',
                    '09/1231212',
                    ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::organizerNotFound($this->organizerId->toString()),
            fn () => $this->updateContributorsRequestHandler->handle($unkownOrganizerRequest)
        );
    }
}
