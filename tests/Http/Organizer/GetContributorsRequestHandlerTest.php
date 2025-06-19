<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private GetContributorsRequestHandler $getContributorsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private OrganizerRepository&MockObject $organizerRepository;

    private ContributorRepository&MockObject $contributorRepository;

    private PermissionVoter&MockObject $permissionVoter;

    private ?string $currentUserId;

    private string $organizerId;

    public function setUp(): void
    {
        $this->organizerRepository = $this->createMock(OrganizerRepository::class);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->permissionVoter = $this->createMock(PermissionVoter::class);
        $this->organizerId = '89251d8a-d776-46cb-83e5-6ded76afbdf9';
        $this->currentUserId = '0b7ca1e2-e2e5-467f-9e7d-0cd481d66ee5';

        $this->getContributorsRequestHandler = new GetContributorsRequestHandler(
            $this->organizerRepository,
            $this->contributorRepository,
            $this->permissionVoter,
            $this->currentUserId
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_getting_contributors(): void
    {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::aanbodBewerken(),
                $this->organizerId,
                $this->currentUserId
            )
            ->willReturn(true);

        $this->contributorRepository->expects($this->once())
            ->method('getContributors')
            ->with(new Uuid($this->organizerId))
            ->willReturn(
                EmailAddresses::fromArray(
                    [
                        new EmailAddress('info@gent.be'),
                        new EmailAddress('an@gent.be'),
                    ]
                )
            );

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId)
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
    public function it_handles_unknown_organizer(): void
    {
        $this->organizerRepository->expects($this->once())
            ->method('load')
            ->with($this->organizerId)
            ->willThrowException(new AggregateNotFoundException());

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId)
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::organizerNotFound($this->organizerId),
            fn () => $this->getContributorsRequestHandler->handle($getContributorsRequest)
        );
    }

    /**
     * @test
     */
    public function it_handles_forbidden_organizers(): void
    {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::aanbodBewerken(),
                $this->organizerId,
                $this->currentUserId
            )
            ->willReturn(false);

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId)
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden(
                sprintf(
                    'User %s has no permission "%s" on resource %s',
                    $this->currentUserId,
                    Permission::aanbodBewerken()->toString(),
                    $this->organizerId
                )
            ),
            fn () => $this->getContributorsRequestHandler->handle($getContributorsRequest)
        );
    }
}
