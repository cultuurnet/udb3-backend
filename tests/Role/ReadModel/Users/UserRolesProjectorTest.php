<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\User\UserIdentityDetails;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UserRolesProjectorTest extends TestCase
{
    /**
     * @var DocumentRepository|MockObject
     */
    private $userRolesDocumentRepository;

    /**
     * @var DocumentRepository|MockObject
     */
    private $roleDetailsDocumentRepository;

    /**
     * @var DocumentRepository|MockObject
     */
    private $roleUsersDocumentRepository;

    /**
     * @var UserRolesProjector
     */
    private $userRolesProjector;

    protected function setUp()
    {
        $this->userRolesDocumentRepository = $this->createMock(
            DocumentRepository::class
        );

        $this->roleDetailsDocumentRepository = $this->createMock(
            DocumentRepository::class
        );

        $this->roleUsersDocumentRepository = $this->createMock(
            DocumentRepository::class
        );

        $this->userRolesProjector = new UserRolesProjector(
            $this->userRolesDocumentRepository,
            $this->roleDetailsDocumentRepository,
            $this->roleUsersDocumentRepository
        );
    }

    /**
     * @test
     */
    public function it_updates_projection_when_document_found_on_user_added_event()
    {
        // The role uuid to which the user will be added.
        $newRoleUuid = new UUID();
        $userAdded = new UserAdded(
            $newRoleUuid,
            new StringLiteral('userId')
        );

        // The new role details document.
        $newRoleDetailsDocument = $this->createRoleDetailsDocument(
            $newRoleUuid,
            'newRole'
        );
        // Which will come from the role details repo.
        $this->roleDetailsDocumentRepository->method('fetch')
            ->with($newRoleUuid)
            ->willReturn($newRoleDetailsDocument);

        // The existing role details document.
        $existingRoleUuid = new UUID();
        $existingRoleDetailsDocument = $this->createRoleDetailsDocument(
            $existingRoleUuid,
            'existingRole'
        );
        // Which is part from the existing user roles document.
        $roles[$existingRoleUuid->toNative()] = $existingRoleDetailsDocument->getBody();
        $this->userRolesDocumentRepository->method('fetch')
            ->with($userAdded->getUserId()->toNative())
            ->willReturn(new JsonDocument(
                $userAdded->getUserId()->toNative(),
                json_encode($roles)
            ));

        // The resulting user role document with 2 roles.
        $roles[$newRoleUuid->toNative()] = $newRoleDetailsDocument->getBody();
        $expectedUserRolesDocument = new JsonDocument(
            $userAdded->getUserId()->toNative(),
            json_encode($roles)
        );

        $this->userRolesDocumentRepository->expects($this->once())
            ->method('save')
            ->with($expectedUserRolesDocument);

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->userRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_when_document_not_found_on_user_added_event()
    {
        $userAdded = new UserAdded(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $roleDetailsDocument = $this->createRoleDetailsDocument(
            $userAdded->getUuid(),
            'roleName'
        );

        $this->roleDetailsDocumentRepository
            ->method('fetch')
            ->with($userAdded->getUuid())
            ->willReturn($roleDetailsDocument);

        $this->userRolesDocumentRepository
            ->method('fetch')
            ->with($userAdded->getUserId())
            ->willThrowException(DocumentDoesNotExist::withId($userAdded->getUserId()->toNative()));

        $roles[$userAdded->getUuid()->toNative()] = $roleDetailsDocument->getBody();
        $jsonDocument = new JsonDocument(
            $userAdded->getUserId()->toNative(),
            json_encode($roles)
        );

        $this->userRolesDocumentRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->userRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_on_user_removed_event()
    {
        $userRemoved = new UserRemoved(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userRemoved->getUuid(),
            $userRemoved
        );

        $jsonDocument = new JsonDocument(
            $userRemoved->getUserId()->toNative(),
            json_encode([$userRemoved->getUuid()->toNative()])
        );

        $this->userRolesDocumentRepository->method('fetch')
            ->with($userRemoved->getUserId())
            ->willReturn($jsonDocument);

        $this->userRolesDocumentRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->userRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_on_role_details_projected_event()
    {
        $roleDetailsProjectedToJSONLD = new RoleDetailsProjectedToJSONLD(
            new UUID()
        );

        // The new role details to apply.
        $newRoleDetailsDocument = $this->createRoleDetailsDocument(
            $roleDetailsProjectedToJSONLD->getUuid(),
            'newRoleName'
        );
        $this->roleDetailsDocumentRepository->method('fetch')
            ->with($roleDetailsProjectedToJSONLD->getUuid()->toNative())
            ->willReturn($newRoleDetailsDocument);

        // The existing role users relations.
        $userIdentityDetails = new UserIdentityDetails(
            'userId',
            'userName',
            'username@company.com'
        );
        $users[$userIdentityDetails->getUserId()] = $userIdentityDetails;
        $roleUsersDocument = new JsonDocument(
            $roleDetailsProjectedToJSONLD->getUuid()->toNative(),
            json_encode($users)
        );
        $this->roleUsersDocumentRepository->method('fetch')
            ->with($roleDetailsProjectedToJSONLD->getUuid()->toNative())
            ->willReturn($roleUsersDocument);

        // The existing user roles relation.
        $oldRoleDetailsDocument = $this->createRoleDetailsDocument(
            $roleDetailsProjectedToJSONLD->getUuid(),
            'oldRoleName'
        );
        $roles[$roleDetailsProjectedToJSONLD->getUuid()->toNative()] = $oldRoleDetailsDocument->getBody();
        $existingUserRolesDocument = new JsonDocument(
            'userId',
            json_encode($roles)
        );
        $this->userRolesDocumentRepository->method('fetch')
            ->with('userId')
            ->willReturn($existingUserRolesDocument);

        // The existing user roles relation needs to be updated with new role details.
        $roles[$roleDetailsProjectedToJSONLD->getUuid()->toNative()] = $newRoleDetailsDocument->getBody();
        $newUserRolesDocument = new JsonDocument(
            'userId',
            json_encode($roles)
        );
        $this->userRolesDocumentRepository->expects($this->once())
            ->method('save')
            ->with($newUserRolesDocument);

        $domainMessage = $this->createDomainMessage(
            $roleDetailsProjectedToJSONLD->getUuid(),
            $roleDetailsProjectedToJSONLD
        );
        $this->userRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_update_projection_when_role_details_not_found_on_user_added_event()
    {
        $userAdded = new UserAdded(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userAdded->getUuid(),
            $userAdded
        );

        $this->roleDetailsDocumentRepository->method('fetch')
            ->with($userAdded->getUuid())
            ->willThrowException(DocumentDoesNotExist::withId($userAdded->getUuid()->toNative()));

        $this->userRolesDocumentRepository->expects($this->never())
            ->method('fetch');

        $this->userRolesDocumentRepository->expects($this->never())
            ->method('save');

        $this->userRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_update_projection_when_document_not_found_on_user_removed_event()
    {
        $userRemoved = new UserRemoved(
            new UUID(),
            new StringLiteral('userId')
        );

        $domainMessage = $this->createDomainMessage(
            $userRemoved->getUuid(),
            $userRemoved
        );

        $this->userRolesDocumentRepository->method('fetch')
            ->with($userRemoved->getUserId())
            ->willThrowException(DocumentDoesNotExist::withId($userRemoved->getUserId()->toNative()));

        $this->userRolesDocumentRepository->expects($this->never())
            ->method('save');

        $this->userRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_update_projection_when_role_details_not_found_on_role_details_projected_event()
    {
        $roleDetailsProjectedToJSONLD = new RoleDetailsProjectedToJSONLD(
            new UUID()
        );

        $this->roleDetailsDocumentRepository->method('fetch')
            ->with($roleDetailsProjectedToJSONLD->getUuid()->toNative())
            ->willThrowException(DocumentDoesNotExist::withId($roleDetailsProjectedToJSONLD->getUuid()->toNative()));

        $this->roleUsersDocumentRepository->expects($this->never())
            ->method('fetch');

        $this->userRolesDocumentRepository->expects($this->never())
            ->method('fetch');

        $this->userRolesDocumentRepository->expects($this->never())
            ->method('save');

        $domainMessage = $this->createDomainMessage(
            $roleDetailsProjectedToJSONLD->getUuid(),
            $roleDetailsProjectedToJSONLD
        );
        $this->userRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_update_projection_when_role_users_not_found_on_role_details_projected_event()
    {
        $roleDetailsProjectedToJSONLD = new RoleDetailsProjectedToJSONLD(
            new UUID()
        );

        $newRoleDetailsDocument = $this->createRoleDetailsDocument(
            $roleDetailsProjectedToJSONLD->getUuid(),
            'newRoleName'
        );
        $this->roleDetailsDocumentRepository->method('fetch')
            ->with($roleDetailsProjectedToJSONLD->getUuid()->toNative())
            ->willReturn($newRoleDetailsDocument);

        $this->roleUsersDocumentRepository->method('fetch')
            ->with($roleDetailsProjectedToJSONLD->getUuid()->toNative())
            ->willThrowException(DocumentDoesNotExist::withId($roleDetailsProjectedToJSONLD->getUuid()->toNative()));

        $this->userRolesDocumentRepository->expects($this->never())
            ->method('save');

        $domainMessage = $this->createDomainMessage(
            $roleDetailsProjectedToJSONLD->getUuid(),
            $roleDetailsProjectedToJSONLD
        );
        $this->userRolesProjector->handle($domainMessage);
    }

    private function createDomainMessage(
        UUID $uuid,
        Serializable $payload
    ): DomainMessage {
        return new DomainMessage(
            $uuid,
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }

    /**
     * @param string $roleName
     * @return JsonDocument
     */
    private function createRoleDetailsDocument(UUID $uuid, $roleName)
    {
        $document = new JsonDocument($uuid->toNative());

        $json = $document->getBody();
        $json->name = $roleName;

        return $document->withBody($json);
    }
}
