<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class GetCreatorOwnershipRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private GetCreatorOwnershipRequestHandler $getCreatorOwnershipRequestHandler;
    private InMemoryDocumentRepository $organizerRepository;
    /**
     * @var UserIdentityResolver&MockObject
     */
    private $userIdentityResolver;
    private OwnershipStatusGuard $ownershipStatusGuard;
    private CurrentUser $currentUser;
    /**
     * @var OwnershipSearchRepository&MockObject
     */
    private $ownershipSearchRepository;
    /**
     * @var PermissionVoter&MockObject
     */
    private $permissionVoter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizerRepository = new InMemoryDocumentRepository();
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);
        $this->permissionVoter = $this->createMock(PermissionVoter::class);
        $this->ownershipStatusGuard = new OwnershipStatusGuard(
            $this->ownershipSearchRepository,
            $this->permissionVoter
        );
        $this->currentUser = new CurrentUser(Uuid::uuid4()->toString());

        $this->getCreatorOwnershipRequestHandler = new GetCreatorOwnershipRequestHandler(
            $this->organizerRepository,
            $this->userIdentityResolver,
            $this->ownershipStatusGuard,
            $this->currentUser
        );
    }

    /**
     * @test
     */
    public function it_handles_getting_the_creator_details(): void
    {
        $ownershipId = Uuid::uuid4()->toString();
        $creatorId = Uuid::uuid4()->toString();

        $creator = new UserIdentityDetails(
            $creatorId,
            'John Doe',
            'john@doe.com',
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                $ownershipId,
                $this->currentUser->getId()
            )
            ->willReturn(true);

        $this->organizerRepository->save(
            new JsonDocument($ownershipId, Json::encode(['creator' => $creatorId]))
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($creatorId)
            ->willReturn($creator);

        $response = $this->getCreatorOwnershipRequestHandler->handle($request);

        $expected = Json::encode([
            'userId' => $creatorId,
            'email' => 'john@doe.com',
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_creator_is_not_found(): void
    {
        $ownershipId = Uuid::uuid4()->toString();
        $creatorId = Uuid::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                $ownershipId,
                $this->currentUser->getId()
            )
            ->willReturn(true);

        $this->organizerRepository->save(
            new JsonDocument($ownershipId, Json::encode(['creator' => $creatorId]))
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($creatorId)
            ->willReturn(null);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::resourceNotFound('Creator', $creatorId),
            fn () => $this->getCreatorOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_organizer_is_not_found(): void
    {
        $ownershipId = Uuid::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                $ownershipId,
                $this->currentUser->getId()
            )
            ->willReturn(true);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::resourceNotFound('Organizer', $ownershipId),
            fn () => $this->getCreatorOwnershipRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_user_is_not_owner(): void
    {
        $ownershipId = Uuid::uuid4()->toString();
        $creatorId = Uuid::uuid4()->toString();

        $creator = new UserIdentityDetails(
            $creatorId,
            'John Doe',
            'john@doe.com',
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBeheren(),
                $ownershipId,
                $this->currentUser->getId()
            )
            ->willReturn(false);

        $this->organizerRepository->save(
            new JsonDocument($ownershipId, Json::encode(['creator' => $creatorId]))
        );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('You are not allowed to get creator for this item'),
            fn () => $this->getCreatorOwnershipRequestHandler->handle($request)
        );
    }
}
