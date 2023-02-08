<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepositoryInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private GetContributorsRequestHandler $getContributorsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private UUID $organizerId;

    /**
     * @var OrganizerRepository|MockObject
     */
    private $organizerRepository;

    /**
     * @var ContributorRepositoryInterface|MockObject
     */
    private $contributorRepository;

    public function setUp(): void
    {
        $this->organizerId = new UUID('4c6c8331-6d2c-44a4-bcec-c7a806b4a8a9');
        $this->organizerRepository = $this->createMock(OrganizerRepository::class);
        $this->contributorRepository = $this->createMock(ContributorRepositoryInterface::class);

        $this->getContributorsRequestHandler = new GetContributorsRequestHandler(
            $this->organizerRepository,
            $this->contributorRepository
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_getting_contributors(
    ): void {
        $this->contributorRepository->expects($this->once())
            ->method('getContributors')
            ->with($this->organizerId)
            ->willReturn(
                EmailAddresses::fromArray(
                    [
                        new EmailAddress('info@gent.be'),
                        new EmailAddress('an@gent.be'),
                    ]
                )
            );

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId->toString())
            ->build('GET');

        $response = $this->getContributorsRequestHandler->handle($getContributorsRequest);

        $this->assertJsonResponse(
            new JsonResponse(['info@gent.be','an@gent.be']),
            $response
        );
    }

    /**
     * @test
     */
    public function it_handles_unknown_organizers(
    ): void {
        $this->organizerRepository->expects($this->once())
            ->method('load')
            ->with($this->organizerId->toString())
            ->willThrowException(new AggregateNotFoundException());

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId->toString())
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::organizerNotFound($this->organizerId->toString()),
            fn () => $this->getContributorsRequestHandler->handle($getContributorsRequest)
        );
    }
}
