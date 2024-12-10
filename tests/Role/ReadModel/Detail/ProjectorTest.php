<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Detail;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\AbstractEvent;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectorTest extends TestCase
{
    private Query $query;

    /**
     * @var DocumentRepository&MockObject
     */
    private $repository;

    private UUID $uuid;

    private string $name;

    private Projector $projector;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid = new UUID('49bd503f-0476-4b18-bc67-f48bf3ae8b57');
        $this->name = 'roleName';

        $this->query = new Query('city:Leuven');
        $this->repository = $this->createMock(DocumentRepository::class);

        $this->projector = new Projector($this->repository);
    }

    /**
     * @test
     */
    public function it_handles_created_when_uuid_unique(): void
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            $this->name
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $roleCreated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_rename(): void
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            $this->name
        );

        $name = 'newRoleName';
        $roleRenamed = new RoleRenamed(
            $this->uuid,
            $name
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $roleCreated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $this->projector->handle($domainMessage);

        $domainMessageRenamed = $this->createDomainMessage(
            $this->uuid,
            $roleRenamed,
            BroadwayDateTime::fromString('2016-06-30T14:25:21+01:00')
        );

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $name;
        $json->permissions = [];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($this->uuid->toString())
            ->willReturn($this->initialDocument());

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessageRenamed);
    }

    /**
     * @test
     */
    public function it_handles_delete(): void
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            $this->name
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $roleCreated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $this->projector->handle($domainMessage);

        $roleDeleted = new RoleDeleted(
            $this->uuid
        );

        $deletedDomainMessage = $this->createDomainMessage(
            $this->uuid,
            $roleDeleted,
            BroadwayDateTime::fromString('2016-06-30T16:25:21+01:00')
        );

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($this->uuid->toString());

        $this->projector->handle($deletedDomainMessage);
    }

    /**
     * @test
     */
    public function it_handles_constraint_added(): void
    {
        $constraintAdded = new ConstraintAdded(
            $this->uuid,
            $this->query
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintAdded,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraint = $this->query->toString();
        $json->constraints->{'v3'} = $this->query->toString();

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($this->uuid->toString())
            ->willReturn($this->initialDocument());

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_constraint_updated(): void
    {
        $constraintUpdated = new ConstraintUpdated(
            $this->uuid,
            new Query('city:Kortrijk OR keywords:"zuidwest uitpas"')
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintUpdated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraint = $constraintUpdated->getQuery()->toString();
        $json->constraints->{'v3'} = $constraintUpdated->getQuery()->toString();

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($this->uuid->toString())
            ->willReturn($this->documentWithConstraint(
                $constraintUpdated->getQuery()
            ));

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_constraint_removed(): void
    {
        $constraintRemoved = new ConstraintRemoved(
            $this->uuid,
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintRemoved,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraint = null;
        $json->constraints->{'v3'} = null;

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($this->uuid->toString())
            ->willReturn(
                $this->documentWithEmptyConstraint()
            );

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_initializes_empty_permissions_on_the_creation_of_a_role(): void
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            'roleName'
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $roleCreated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_the_addition_of_a_permission(): void
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            'roleName'
        );

        $permission = Permission::aanbodBewerken();

        $domainMessageCreated = $this->createDomainMessage(
            $this->uuid,
            $roleCreated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $this->projector->handle($domainMessageCreated);

        $permissionAdded = new PermissionAdded(
            $this->uuid,
            $permission
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $permissionAdded,
            BroadwayDateTime::fromString('2016-06-30T14:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = ['AANBOD_BEWERKEN'];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($this->uuid->toString())
            ->willReturn($this->initialDocument());

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_the_removal_of_a_permission(): void
    {
        $permission = Permission::aanbodBewerken();

        $permissionAdded = new PermissionAdded(
            $this->uuid,
            $permission
        );

        $permissionRemoved = new PermissionRemoved(
            $this->uuid,
            $permission
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $permissionAdded,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $this->repository->expects($this->any())
            ->method('fetch')
            ->willReturn($this->initialDocument());

        $this->projector->handle($domainMessage);

        $domainMessageRemoved = $this->createDomainMessage(
            $this->uuid,
            $permissionRemoved,
            BroadwayDateTime::fromString('2016-06-30T15:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($this->uuid->toString())
            ->willReturn($this->documentWithPermission($permission));

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessageRemoved);
    }

    private function createDomainMessage(
        UUID $id,
        AbstractEvent $payload,
        BroadwayDateTime $dateTime = null
    ): DomainMessage {
        if (null === $dateTime) {
            $dateTime = BroadwayDateTime::now();
        }

        return new DomainMessage(
            $id->toString(),
            0,
            new Metadata(),
            $payload,
            $dateTime
        );
    }

    private function initialDocument(): JsonDocument
    {
        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];

        return $document->withBody($json);
    }

    private function documentWithPermission(Permission $permission): JsonDocument
    {
        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = ['AANBOD_BEWERKEN'];

        return $document->withBody($json);
    }

    private function documentWithConstraint(?Query $query): JsonDocument
    {
        $document = new JsonDocument($this->uuid->toString());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toString();
        $json->name = $this->name;
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{'v3'} =
            $query ? $query->toString() : null;

        return $document->withBody($json);
    }

    private function documentWithEmptyConstraint(): JsonDocument
    {
        return $this->documentWithConstraint(null);
    }
}
