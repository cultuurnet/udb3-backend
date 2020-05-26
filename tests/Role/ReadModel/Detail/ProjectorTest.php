<?php

namespace CultuurNet\UDB3\Role\ReadModel\Detail;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
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
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class ProjectorTest extends TestCase
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var UUID
     */
    private $constraintUuid;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var StringLiteral
     */
    private $name;

    /**
     * @var Projector
     */
    private $projector;

    public function setUp()
    {
        parent::setUp();

        $this->uuid = new UUID();
        $this->name = new StringLiteral('roleName');

        $this->constraintUuid = new UUID();
        $this->query = new Query('city:Leuven');
        $this->repository = $this->createMock(DocumentRepositoryInterface::class);

        $this->projector = new Projector($this->repository);
    }

    /**
     * @test
     */
    public function it_handles_created_when_uuid_unique()
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


        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
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
    public function it_handles_rename()
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            $this->name
        );

        $name = new StringLiteral('newRoleName');
        $roleRenamed = new RoleRenamed(
            $this->uuid,
            $name
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $roleCreated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $this->projector->handle($domainMessage);

        $domainMessageRenamed = $this->createDomainMessage(
            $this->uuid,
            $roleRenamed,
            BroadwayDateTime::fromString('2016-06-30T14:25:21+01:00')
        );

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $name->toNative();
        $json->permissions = [];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
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
    public function it_handles_delete()
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
            ->with($this->uuid->toNative());

        $this->projector->handle($deletedDomainMessage);
    }

    /**
     * @test
     */
    public function it_handles_constraint_added()
    {
        $constraintAdded = new ConstraintAdded(
            $this->uuid,
            SapiVersion::V2(),
            $this->query
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintAdded,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{$constraintAdded->getSapiVersion()->toNative()} = $this->query->toNative();

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
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
    public function it_handles_constraint_added_with_other_sapi_version()
    {
        $constraintAdded = new ConstraintAdded(
            $this->uuid,
            SapiVersion::V3(),
            $this->query
        );

        $queryV2 = new Query('city_v2: 3000');

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintAdded,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{SapiVersion::V2} = $queryV2->toNative();
        $json->constraints->{$constraintAdded->getSapiVersion()->toNative()} = $this->query->toNative();

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
            ->willReturn($this->documentWithConstraint(SapiVersion::V2(), $queryV2));

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
    public function it_handles_constraint_updated()
    {
        $constraintUpdated = new ConstraintUpdated(
            $this->uuid,
            SapiVersion::V2(),
            new Query('city:Kortrijk OR keywords:"zuidwest uitpas"')
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintUpdated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{$constraintUpdated->getSapiVersion()->toNative()} =
            $constraintUpdated->getQuery()->toNative();

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
            ->willReturn($this->documentWithConstraint(
                $constraintUpdated->getSapiVersion(),
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
    public function it_handles_constraint_updated_with_multiple_constraints()
    {
        $constraintUpdated = new ConstraintUpdated(
            $this->uuid,
            SapiVersion::V3(),
            new Query('city_v3:3000')
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintUpdated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{SapiVersion::V2} = 'city:Kortrijk OR keywords:"zuidwest uitpas"';
        $json->constraints->{$constraintUpdated->getSapiVersion()->toNative()} =
            $constraintUpdated->getQuery()->toNative();

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
            ->willReturn($this->documentWithConstraint(
                SapiVersion::V2(),
                new Query('city:Kortrijk OR keywords:"zuidwest uitpas"')
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
    public function it_handles_constraint_removed()
    {
        $constraintRemoved = new ConstraintRemoved(
            $this->uuid,
            SapiVersion::V2()
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintRemoved,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{$constraintRemoved->getSapiVersion()->toNative()} =
            null;

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
            ->willReturn($this->documentWithEmptyConstraint(
                $constraintRemoved->getSapiVersion()
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
    public function it_handles_constraint_removed_with_multiple_constraints()
    {
        $constraintRemoved = new ConstraintRemoved(
            $this->uuid,
            SapiVersion::V3()
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $constraintRemoved,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $initialDocument = $this->documentWithConstraint(
            SapiVersion::V2(),
            new Query('city:Kortrijk OR keywords:"zuidwest uitpas"')
        );

        $initialDocument = $this->documentWithExtraConstraint(
            $initialDocument,
            SapiVersion::V3(),
            new Query('city_v3:2300')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{SapiVersion::V2} = 'city:Kortrijk OR keywords:"zuidwest uitpas"';
        $json->constraints->{$constraintRemoved->getSapiVersion()->toNative()} =
            null;

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
            ->willReturn($initialDocument);

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
    public function it_initializes_empty_permissions_on_the_creation_of_a_role()
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            new StringLiteral('roleName')
        );

        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            $roleCreated,
            BroadwayDateTime::fromString('2016-06-30T13:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
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
    public function it_handles_the_addition_of_a_permission()
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            new StringLiteral('roleName')
        );

        $permission = Permission::AANBOD_BEWERKEN();

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

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [$permission->getName()];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
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
    public function it_handles_the_removal_of_a_permission()
    {
        $permission = Permission::AANBOD_BEWERKEN();

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

        $this->projector->handle($domainMessage);

        $domainMessageRemoved = $this->createDomainMessage(
            $this->uuid,
            $permissionRemoved,
            BroadwayDateTime::fromString('2016-06-30T15:25:21+01:00')
        );

        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];

        $document = $document->withBody($json);

        $this->repository->expects($this->once())
            ->method('get')
            ->with($this->uuid->toNative())
            ->willReturn($this->documentWithPermission($permission));

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $document
            );

        $this->projector->handle($domainMessageRemoved);
    }

    /**
     * @param string $id
     * @param AbstractEvent $payload
     * @param BroadwayDateTime $dateTime
     * @return DomainMessage
     */
    private function createDomainMessage($id, $payload, BroadwayDateTime $dateTime = null)
    {
        if (null === $dateTime) {
            $dateTime = BroadwayDateTime::now();
        }

        return new DomainMessage(
            $id,
            0,
            new Metadata(),
            $payload,
            $dateTime
        );
    }

    /**
     * @return JsonDocument
     */
    private function initialDocument()
    {
        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];

        $document = $document->withBody($json);

        return $document;
    }

    /**
     * @param Permission $permission
     * @return JsonDocument
     */
    private function documentWithPermission(Permission $permission)
    {
        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [$permission->getName()];

        $document = $document->withBody($json);

        return $document;
    }

    /**
     * @param SapiVersion $sapiVersion
     * @param Query|null $query
     * @return JsonDocument
     */
    private function documentWithConstraint(SapiVersion $sapiVersion, ?Query $query): JsonDocument
    {
        $document = new JsonDocument($this->uuid->toNative());

        $json = $document->getBody();
        $json->uuid = $this->uuid->toNative();
        $json->name = $this->name->toNative();
        $json->permissions = [];
        $json->constraints = new \stdClass();
        $json->constraints->{$sapiVersion->toNative()} =
            $query ? $query->toNative() : null;

        $document = $document->withBody($json);

        return $document;
    }

    /**
     * @param SapiVersion $sapiVersion
     * @return JsonDocument
     */
    private function documentWithEmptyConstraint(SapiVersion $sapiVersion): JsonDocument
    {
        return $this->documentWithConstraint($sapiVersion, null);
    }

    /**
     * @param JsonDocument $document
     * @param SapiVersion $sapiVersion
     * @param Query $query
     * @return JsonDocument
     */
    private function documentWithExtraConstraint(
        JsonDocument $document,
        SapiVersion $sapiVersion,
        Query $query
    ): JsonDocument {
        $json = $document->getBody();
        $json->constraints->{$sapiVersion->toNative()} = $query->toNative();

        $document = $document->withBody($json);

        return $document;
    }
}
