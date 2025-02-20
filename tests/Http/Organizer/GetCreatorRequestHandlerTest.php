<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class GetCreatorRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private GetCreatorRequestHandler $getCreatorRequestHandler;

    /**
     * @var DocumentRepository&MockObject
     */
    private $organizerRepository;
    /**
     * @var UserIdentityResolver&MockObject
     */
    private $userIdentityResolver;
    private CurrentUser $currentUser;
    /**
     * @var PermissionVoter&MockObject
     */
    private $permissionVoter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizerRepository = $this->createMock(DocumentRepository::class);
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $this->permissionVoter = $this->createMock(PermissionVoter::class);
        $this->currentUser = new CurrentUser(Uuid::uuid4()->toString());
        $this->getCreatorRequestHandler = new GetCreatorRequestHandler(
            $this->organizerRepository,
            $this->userIdentityResolver,
            $this->permissionVoter,
            $this->currentUser,
        );
    }

    /**
     * @test
     */
    public function it_handles_getting_the_creator_details(): void
    {
        $creatorId = Uuid::uuid4()->toString();
        $organizerId = Uuid::uuid4()->toString();

        $creator = new UserIdentityDetails(
            $creatorId,
            'John Doe',
            'john@doe.com',
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $organizerId)
            ->build('GET');

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(
                new JsonDocument($organizerId, Json::encode(['creator' => $creatorId]))
            );

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBewerken(),
                $organizerId,
                $this->currentUser->getId()
            )
            ->willReturn(true);

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($creatorId)
            ->willReturn($creator);

        $response = $this->getCreatorRequestHandler->handle($request);

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
        $creatorId = Uuid::uuid4()->toString();
        $organizerId = Uuid::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $organizerId)
            ->build('GET');

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(
                new JsonDocument($organizerId, Json::encode(['creator' => $creatorId]))
            );

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBewerken(),
                $organizerId,
                $this->currentUser->getId()
            )
            ->willReturn(true);

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($creatorId)
            ->willReturn(null);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::resourceNotFound('Creator', $creatorId),
            fn () => $this->getCreatorRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_organizer_is_not_found(): void
    {
        $organizerId = Uuid::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $organizerId)
            ->build('GET');

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBewerken(),
                $organizerId,
                $this->currentUser->getId()
            )
            ->willReturn(true);

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willThrowException(new DocumentDoesNotExist());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::resourceNotFound('Organizer', $organizerId),
            fn () => $this->getCreatorRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_user_is_not_owner(): void
    {
        $organizerId = Uuid::uuid4()->toString();
        $creatorId = Uuid::uuid4()->toString();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $organizerId)
            ->build('GET');

        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::organisatiesBewerken(),
                $organizerId,
                $this->currentUser->getId()
            )
            ->willReturn(false);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('You are not allowed to get creator for this item'),
            fn () => $this->getCreatorRequestHandler->handle($request)
        );
    }
}
