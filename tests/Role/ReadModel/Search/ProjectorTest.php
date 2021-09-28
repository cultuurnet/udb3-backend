<?php

declare(strict_types=1);

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
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class ProjectorTest extends TestCase
{
    private DomainMessage $domainMessage;

    private Projector $projector;

    private UUID $uuid;

    private $repository;

    public function setUp(): void
    {
        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->projector = new Projector($this->repository);
        $this->domainMessage = new DomainMessage('id', 0, new Metadata(), '', DateTime::now());
        $this->uuid = new UUID();
    }

    /**
     * @test
     */
    public function it_can_project_a_created_role(): void
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
    public function it_can_project_a_renamed_role(): void
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
    public function it_can_project_a_deleted_role(): void
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
    public function it_calls_update_constraint_on_constraint_created_event(): void
    {
        $constraintAdded = new ConstraintAdded(
            new UUID(),
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
    public function it_calls_update_constraint_on_constraint_updated_event(): void
    {
        $constraintUpdated = new ConstraintUpdated(
            new UUID(),
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
    public function it_calls_update_constraint_on_constraint_removed_event(): void
    {
        $constraintRemoved = new ConstraintRemoved(new UUID());
        $domainMessage = $this->createDomainMessage($constraintRemoved);

        $this->repository->expects($this->once())
            ->method('updateConstraint')
            ->with($constraintRemoved->getUuid());

        $this->projector->handle($domainMessage);
    }

    private function createDomainMessage($payload): DomainMessage
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
