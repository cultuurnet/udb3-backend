<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserPermissionsProjectorTest extends TestCase
{
    /**
     * @var UserPermissionsWriteRepositoryInterface&MockObject
     */
    private $userPermissionsWriteRepository;

    private UserPermissionsProjector $userPermissionsProjector;

    protected function setUp(): void
    {
        $this->userPermissionsWriteRepository = $this->createMock(
            UserPermissionsWriteRepositoryInterface::class
        );

        $this->userPermissionsProjector = new UserPermissionsProjector(
            $this->userPermissionsWriteRepository
        );
    }

    /**
     * @test
     */
    public function it_calls_remove_role_on_role_deleted_event(): void
    {
        $roleDeleted = new RoleDeleted(new UUID('bd7175de-f72f-49fd-9c40-2dc12cea985d'));
        $domainMessage = $this->createDomainMessage($roleDeleted);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('removeRole')
            ->with($roleDeleted->getUuid());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_add_user_role_on_user_added_event(): void
    {
        $userAdded = new UserAdded(new UUID('ae51b740-9d8b-4868-ae15-ecbb2649b8e1'), 'userId');
        $domainMessage = $this->createDomainMessage($userAdded);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('addUserRole')
            ->with($userAdded->getUserId(), $userAdded->getUuid());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_remove_user_role_on_user_removed_event(): void
    {
        $userRemoved = new UserRemoved(new UUID('b3154dd0-3109-4133-9f81-29703e64c803'), 'userId');
        $domainMessage = $this->createDomainMessage($userRemoved);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('removeUserRole')
            ->with($userRemoved->getUserId(), $userRemoved->getUuid());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_add_role_permission_on_permission_added_event(): void
    {
        $permissionAdded = new PermissionAdded(new UUID('e4d01227-741f-4cb0-9f77-08f1e9700081'), Permission::aanbodModereren());
        $domainMessage = $this->createDomainMessage($permissionAdded);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('addRolePermission')
            ->with($permissionAdded->getUuid(), $permissionAdded->getPermission());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_remove_role_permission_on_permission_removed_event(): void
    {
        $permissionRemoved = new PermissionRemoved(new UUID('323de7ef-0430-4782-884a-9453417a6a90'), Permission::aanbodModereren());
        $domainMessage = $this->createDomainMessage($permissionRemoved);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('removeRolePermission')
            ->with($permissionRemoved->getUuid(), $permissionRemoved->getPermission());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    private function createDomainMessage(Serializable $payload): DomainMessage
    {
        return new DomainMessage(
            'id',
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }
}
