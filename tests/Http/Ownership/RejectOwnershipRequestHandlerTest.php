<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Commands\RejectOwnership;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RejectOwnershipRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    /** @var OwnershipSearchRepository&MockObject */
    private $ownerShipSearchRepository;

    /** @var PermissionVoter&MockObject */
    private $permissionVoter;

    private RejectOwnershipRequestHandler $rejectOwnershipRequestHandler;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->ownerShipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->permissionVoter = $this->createMock(PermissionVoter::class);

        $this->rejectOwnershipRequestHandler = new RejectOwnershipRequestHandler(
            $this->commandBus,
            new CurrentUser('auth0|63e22626e39a8ca1264bd29b'),
            new OwnershipStatusGuard(
                $this->ownerShipSearchRepository,
                $this->permissionVoter
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_rejecting_an_ownership(): void
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
                'auth0|63e22626e39a8ca1264bd29b',
                OwnershipState::requested()->toString()
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $response = $this->rejectOwnershipRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new RejectOwnership(new Uuid('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_handles_rejecting_an_ownership_as_god_user(): void
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
                'auth0|63e22626e39a8ca1264bd29b',
                OwnershipState::requested()->toString()
            ));

        $this->permissionVoter->expects($this->never())
            ->method('isAllowed');

        $response = $this->rejectOwnershipRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new RejectOwnership(new Uuid('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')),
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
            fn () => $this->rejectOwnershipRequestHandler->handle($request)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_the_user_is_not_allowed_to_reject_the_ownership(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        CurrentUser::configureGodUserIds([]);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('POST');

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getById')
            ->willReturn(new OwnershipItem(
                $ownershipId,
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                'auth0|63e22626e39a8ca1264bd29b',
                OwnershipState::requested()->toString()
            ));

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('You are not allowed to reject this ownership'),
            fn () => $this->rejectOwnershipRequestHandler->handle($request)
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }
}
