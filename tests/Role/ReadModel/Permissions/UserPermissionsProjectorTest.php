<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsProjectorTest extends TestCase
{
    /**
     * @var UserPermissionsWriteRepositoryInterface|MockObject
     */
    private $userPermissionsWriteRepository;

    /**
     * @var UserPermissionsProjector
     */
    private $userPermissionsProjector;

    protected function setUp()
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
    public function it_calls_remove_role_on_role_deleted_event()
    {
        $roleDeleted = new RoleDeleted(new UUID());
        $domainMessage = $this->createDomainMessage($roleDeleted);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('removeRole')
            ->with($roleDeleted->getUuid());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_add_user_role_on_user_added_event()
    {
        $userAdded = new UserAdded(new UUID(), new StringLiteral('userId'));
        $domainMessage = $this->createDomainMessage($userAdded);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('addUserRole')
            ->with($userAdded->getUserId(), $userAdded->getUuid());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_remove_user_role_on_user_removed_event()
    {
        $userRemoved = new UserRemoved(new UUID(), new StringLiteral('userId'));
        $domainMessage = $this->createDomainMessage($userRemoved);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('removeUserRole')
            ->with($userRemoved->getUserId(), $userRemoved->getUuid());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_add_role_permission_on_permission_added_event()
    {
        $permissionAdded = new PermissionAdded(new UUID(), Permission::AANBOD_MODEREREN());
        $domainMessage = $this->createDomainMessage($permissionAdded);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('addRolePermission')
            ->with($permissionAdded->getUuid(), $permissionAdded->getPermission());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_remove_role_permission_on_permission_removed_event()
    {
        $permissionRemoved = new PermissionRemoved(new UUID(), Permission::AANBOD_MODEREREN());
        $domainMessage = $this->createDomainMessage($permissionRemoved);

        $this->userPermissionsWriteRepository->expects($this->once())
            ->method('removeRolePermission')
            ->with($permissionRemoved->getUuid(), $permissionRemoved->getPermission());

        $this->userPermissionsProjector->handle($domainMessage);
    }

    /**
     * @param SerializableInterface $payload
     * @return DomainMessage
     */
    private function createDomainMessage(SerializableInterface $payload)
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
