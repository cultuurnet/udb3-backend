<?php

namespace CultuurNet\UDB3\Role\ReadModel\Search;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class ProjectorTest extends TestCase
{
    /**
     * @var DomainMessage
     */
    private $domainMessage;

    /**
     * @var Projector
     */
    private $projector;

    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var SapiVersion
     */
    private $sapiVersion;

    public function setUp()
    {
        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->sapiVersion = SapiVersion::V2();
        $this->projector = new Projector($this->repository, $this->sapiVersion);
        $this->domainMessage = new DomainMessage('id', 0, new Metadata(), '', DateTime::now());
        $this->uuid = new UUID();
    }

    /**
     * @test
     */
    public function it_can_project_a_created_role()
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            new StringLiteral('role_name')
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->uuid->toNative(), 'role_name');

        $this->projector->applyRoleCreated($roleCreated, $this->domainMessage);
    }

    /**
     * @test
     */
    public function it_can_project_a_renamed_role()
    {
        $roleRenamed = new RoleRenamed(
            $this->uuid,
            new StringLiteral('role_name')
        );

        $this->repository
            ->expects($this->once())
            ->method('updateName')
            ->with($this->uuid->toNative(), 'role_name');

        $this->projector->applyRoleRenamed($roleRenamed, $this->domainMessage);
    }

    /**
     * @test
     */
    public function it_can_project_a_deleted_role()
    {
        $roleDeleted = new RoleDeleted(
            $this->uuid
        );

        $this->repository
            ->expects($this->once())
            ->method('remove')
            ->with($this->uuid->toNative());

        $this->projector->applyRoleDeleted($roleDeleted, $this->domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_update_constraint_on_constraint_created_event()
    {
        $constraintAdded = new ConstraintAdded(
            new UUID(),
            SapiVersion::V2(),
            new Query('zipCode:3000')
        );
        $domainMessage = $this->createDomainMessage($constraintAdded);

        $this->repository->expects($this->once())
            ->method('updateConstraint')
            ->with($constraintAdded->getUuid(), $constraintAdded->getQuery());

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_call_update_constraint_on_constraint_created_event_when_sapi_does_not_match()
    {
        $constraintAdded = new ConstraintAdded(
            new UUID(),
            SapiVersion::V3(),
            new Query('zipCode:3000')
        );
        $domainMessage = $this->createDomainMessage($constraintAdded);

        $this->repository->expects($this->never())
            ->method('updateConstraint')
            ->with($constraintAdded->getUuid(), $constraintAdded->getQuery());

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_update_constraint_on_constraint_updated_event()
    {
        $constraintUpdated = new ConstraintUpdated(
            new UUID(),
            SapiVersion::V2(),
            new Query('zipCode:3000')
        );
        $domainMessage = $this->createDomainMessage($constraintUpdated);

        $this->repository->expects($this->once())
            ->method('updateConstraint')
            ->with($constraintUpdated->getUuid(), $constraintUpdated->getQuery());

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_calls_update_constraint_on_constraint_updated_event_when_sapi_does_not_match()
    {
        $constraintUpdated = new ConstraintUpdated(
            new UUID(),
            SapiVersion::V3(),
            new Query('zipCode:3000')
        );
        $domainMessage = $this->createDomainMessage($constraintUpdated);

        $this->repository->expects($this->never())
            ->method('updateConstraint')
            ->with($constraintUpdated->getUuid(), $constraintUpdated->getQuery());

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_update_constraint_on_constraint_removed_event()
    {
        $constraintRemoved = new ConstraintRemoved(new UUID(), SapiVersion::V2());
        $domainMessage = $this->createDomainMessage($constraintRemoved);

        $this->repository->expects($this->once())
            ->method('updateConstraint')
            ->with($constraintRemoved->getUuid());

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_calls_update_constraint_on_constraint_removed_event_when_sapi_does_not_match()
    {
        $constraintRemoved = new ConstraintRemoved(new UUID(), SapiVersion::V3());
        $domainMessage = $this->createDomainMessage($constraintRemoved);

        $this->repository->expects($this->never())
            ->method('updateConstraint')
            ->with($constraintRemoved->getUuid());

        $this->projector->handle($domainMessage);
    }

    /**
     * @param $payload
     * @return DomainMessage
     */
    private function createDomainMessage($payload)
    {
        return new DomainMessage(
            'id',
            1,
            new Metadata(),
            $payload,
            DateTime::now()
        );
    }
}
