<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleUsersProjectorTest extends TestCase
{
    /**
     * @var DocumentRepository&MockObject
     */
    private $repository;

    /**
     * @var UserIdentityResolver&MockObject
     */
    private $userIdentityResolver;

    private RoleUsersProjector $roleUsersProjector;

    private UserIdentityDetails $userIdentityDetail;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DocumentRepository::class);

        $this->userIdentityResolver = $this->createMock(
            UserIdentityResolver::class
        );

        $this->roleUsersProjector = new RoleUsersProjector(
            $this->repository,
            $this->userIdentityResolver
        );

        $this->userIdentityDetail = new UserIdentityDetails(
            'userId',
            'username',
            'username@company.be'
        );
    }

    /**
     * @test
     */
    public function it_creates_projection_with_empty_list_of_users_on_role_created_event(): void
    {
        $roleCreated = new RoleCreated(
            new Uuid('1be501c0-4e1c-4c92-a97d-33b3839897db'),
            'roleName'
        );

        $domainMessage = $this->createDomainMessage(
            $roleCreated->getUuid(),
            $roleCreated
        );

        $jsonDocument = $this->createEmptyJsonDocument($roleCreated->getUuid());
        $this->repository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_projection_on_role_deleted_event(): void
    {
        $roleDeleted = new RoleDeleted(
            new Uuid('6f5e21ce-5695-4e22-b395-ff0005ebb191')
        );

        $domainMessage = $this->createDomainMessage(
            $roleDeleted->getUuid(),
            $roleDeleted
        );

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($roleDeleted->getUuid()->toString());

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_with_user_identity_on_user_added_event(): void
    {
        $userAdded = new UserAdded(
            new Uuid('e0b91781-7aef-464a-8857-221fb347e279'),
            'userId'
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->mockFetch(
            $userAdded->getUuid(),
            $this->createEmptyJsonDocument($userAdded->getUuid())
        );

        $this->mockGetUserById(
            $this->userIdentityDetail->getUserId(),
            $this->userIdentityDetail
        );

        $jsonDocument = $this->createJsonDocumentWithUserIdentityDetail(
            $userAdded->getUuid(),
            $this->userIdentityDetail
        );

        $this->repository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_user_identity_from_projection_on_user_removed_event(): void
    {
        $userRemoved = new UserRemoved(
            new Uuid('33981daf-91d0-4113-a7f4-655e645d9930'),
            'userId'
        );

        $domainMessage = $this->createDomainMessage(
            $userRemoved->getUuid(),
            $userRemoved
        );

        $this->mockFetch(
            $userRemoved->getUuid(),
            $this->createJsonDocumentWithUserIdentityDetail(
                $userRemoved->getUuid(),
                $this->userIdentityDetail
            )
        );

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->createEmptyJsonDocument($userRemoved->getUuid()));

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_save_a_projection_when_document_not_found_on_user_added_event(): void
    {
        $userAdded = new UserAdded(
            new Uuid('45819178-ac36-4f3d-b12a-4b48cdd442fd'),
            'userId'
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->repository
            ->method('fetch')
            ->with($userAdded->getUuid()->toString())
            ->willThrowException(DocumentDoesNotExist::withId($userAdded->getUuid()->toString()));

        $this->userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $this->repository->expects($this->never())
            ->method('save');

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_save_a_projection_when_user_details_not_found_on_user_added_event(): void
    {
        $userAdded = new UserAdded(
            new Uuid('e9fb3606-5ed8-416a-b959-dabe3fa7f437'),
            'userId'
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->mockFetch(
            $userAdded->getUuid(),
            $this->createEmptyJsonDocument($userAdded->getUuid())
        );

        $this->mockGetUserById('userId');

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with('userId');

        $this->repository->expects($this->never())
            ->method('save');

        $this->roleUsersProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_save_a_projection_when_document_not_found_on_user_removed_event(): void
    {
        $userRemoved = new UserRemoved(
            new Uuid('2b78616d-502b-47f4-95cf-b9bef3ad3a05'),
            'userId'
        );

        $domainMessage = $this->createDomainMessage(
            $userRemoved->getUuid(),
            $userRemoved
        );

        $this->repository
            ->method('fetch')
            ->with($userRemoved->getUuid()->toString())
            ->willThrowException(DocumentDoesNotExist::withId($userRemoved->getUuid()->toString()));

        $this->userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $this->repository->expects($this->never())
            ->method('save');

        $this->roleUsersProjector->handle($domainMessage);
    }

    private function mockFetch(Uuid $uuid, JsonDocument $jsonDocument = null): void
    {
        $this->repository
            ->method('fetch')
            ->with($uuid->toString())
            ->willReturn($jsonDocument);
    }

    private function mockGetUserById(
        string $userId,
        UserIdentityDetails $userIdentityDetails = null
    ): void {
        $this->userIdentityResolver
            ->method('getUserById')
            ->with($userId)
            ->willReturn($userIdentityDetails);
    }

    private function createDomainMessage(
        Uuid $uuid,
        Serializable $payload
    ): DomainMessage {
        return new DomainMessage(
            $uuid->toString(),
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }

    private function createEmptyJsonDocument(Uuid $uuid): JsonDocument
    {
        return new JsonDocument(
            $uuid->toString(),
            Json::encode([])
        );
    }

    private function createJsonDocumentWithUserIdentityDetail(
        Uuid $uuid,
        UserIdentityDetails $userIdentityDetail
    ): JsonDocument {
        $userIdentityDetails = [];

        $key = $userIdentityDetail->getUserId();
        $userIdentityDetails[$key] = $userIdentityDetail;

        return new JsonDocument($uuid->toString(), Json::encode($userIdentityDetails));
    }
}
