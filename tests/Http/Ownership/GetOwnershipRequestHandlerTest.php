<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetOwnershipRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private InMemoryDocumentRepository $ownershipRepository;

    private OwnershipSearchRepository&MockObject $ownerShipSearchRepository;

    private PermissionVoter&MockObject $permissionVoter;

    private GetOwnershipRequestHandler $getOwnershipRequestHandler;

    protected function setUp(): void
    {
        $this->ownershipRepository = new InMemoryDocumentRepository();

        $this->ownerShipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->permissionVoter = $this->createMock(PermissionVoter::class);

        $this->getOwnershipRequestHandler = new GetOwnershipRequestHandler(
            $this->ownershipRepository,
            new CurrentUser('auth0|63e22626e39a8ca1264bd29b'),
            new OwnershipStatusGuard(
                $this->ownerShipSearchRepository,
                $this->permissionVoter
            )
        );

        parent::setUp();
    }

    /**
     * @test
     */
    public function it_handles_getting_an_ownership_as_owner(): void
    {
        CurrentUser::configureGodUserIds([]);

        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $this->givenItFindsAnOwnershipForUser($ownershipId, 'auth0|63e22626e39a8ca1264bd29b');

        $body = $this->givenThereIsAnOwnershipDocument($ownershipId);

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');
        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_handles_getting_an_ownership_as_admin(): void
    {
        CurrentUser::configureGodUserIds(['auth0|63e22626e39a8ca1264bd29b']);

        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $this->givenItFindsAnOwnershipForUser($ownershipId, 'auth0|63e22626e39a8ca1264bd29b');

        $body = $this->givenThereIsAnOwnershipDocument($ownershipId);

        $this->permissionVoter->expects($this->never())
            ->method('isAllowed');

        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');
        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_handles_getting_an_ownership_with_permission(): void
    {
        CurrentUser::configureGodUserIds([]);

        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $this->givenItFindsAnOwnershipForUser($ownershipId, 'auth0|for_another_user');

        $body = $this->givenThereIsAnOwnershipDocument($ownershipId);

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');
        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_forbids_getting_an_ownership_without_permission(): void
    {
        CurrentUser::configureGodUserIds([]);

        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $this->givenItFindsAnOwnershipForUser($ownershipId, 'auth0|for_another_user');

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $this->givenThereIsAnOwnershipDocument($ownershipId);

        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');
        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('You are not allowed to get this ownership'),
            fn () => $this->getOwnershipRequestHandler->handle($getOwnershipRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_ownership_is_not_found(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getById')
            ->willThrowException(OwnershipItemNotFound::byId($ownershipId));

        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');
        $this->assertCallableThrowsApiProblem(
            ApiProblem::ownershipNotFound($ownershipId),
            fn () => $this->getOwnershipRequestHandler->handle($getOwnershipRequest)
        );
    }

    private function givenItFindsAnOwnershipForUser(string $ownershipId, string $userId): void
    {
        $this->ownerShipSearchRepository->expects($this->once())
            ->method('getById')
            ->willReturn(new OwnershipItem(
                $ownershipId,
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                $userId,
                OwnershipState::requested()->toString()
            ));
    }

    private function givenThereIsAnOwnershipDocument(string $ownershipId): string
    {
        $body = Json::encode([
            'id' => $ownershipId,
            'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
        ]);
        $this->ownershipRepository->save(new JsonDocument($ownershipId, $body));

        return $body;
    }
}
