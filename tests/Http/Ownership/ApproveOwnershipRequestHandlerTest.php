<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Commands\ApproveOwnership;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApproveOwnershipRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    /** @var OwnershipSearchRepository&MockObject */
    private $ownerShipSearchRepository;

    /** @var PermissionVoter&MockObject */
    private $permissionVoter;

    private ApproveOwnershipRequestHandler $approveOwnershipRequestHandler;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->ownerShipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->permissionVoter = $this->createMock(PermissionVoter::class);

        $this->approveOwnershipRequestHandler = new ApproveOwnershipRequestHandler(
            $this->commandBus,
            $this->ownerShipSearchRepository,
            new CurrentUser('auth0|63e22626e39a8ca1264bd29b'),
            $this->permissionVoter
        );
    }

    /**
     * @test
     */
    public function it_handles_approving_an_ownership(): void
    {
        CurrentUser::configureGodUserIds([]);

        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('POST');

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getById')
            ->willReturn(new OwnershipItem(
                $ownershipId,
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                'auth0|63e22626e39a8ca1264bd29b'
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $response = $this->approveOwnershipRequestHandler->handle($request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals(
            [
                new ApproveOwnership(
                    new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                    new UserId('auth0|63e22626e39a8ca1264bd29b')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_handles_approving_an_ownership_as_god_user(): void
    {
        CurrentUser::configureGodUserIds(['auth0|63e22626e39a8ca1264bd29b']);

        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('POST');

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getById')
            ->willReturn(new OwnershipItem(
                $ownershipId,
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                'auth0|63e22626e39a8ca1264bd29b'
            ));

        $this->permissionVoter->expects($this->never())
            ->method('isAllowed');

        $response = $this->approveOwnershipRequestHandler->handle($request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals(
            [
                new ApproveOwnership(
                    new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                    new UserId('auth0|63e22626e39a8ca1264bd29b')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_the_ownership_is_not_found(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getById')
            ->willThrowException(OwnershipItemNotFound::byId($ownershipId));

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::ownershipNotFound('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            fn () => $this->approveOwnershipRequestHandler->handle($request)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_requester_is_not_allowed_to_approve_ownership(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        CurrentUser::configureGodUserIds([]);

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getById')
            ->willReturn(new OwnershipItem(
                $ownershipId,
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                'auth0|63e22626e39a8ca1264bd29b'
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('You are not allowed to approve this ownership'),
            fn () => $this->approveOwnershipRequestHandler->handle($request)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }
}
