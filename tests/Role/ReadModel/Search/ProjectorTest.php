<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectorTest extends TestCase
{
    private DomainMessage $domainMessage;

    private Projector $projector;

    private Uuid $uuid;

    /** @var RepositoryInterface&MockObject */
    private $repository;

    public function setUp(): void
    {
        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->projector = new Projector($this->repository);
        $this->domainMessage = new DomainMessage('id', 0, new Metadata(), '', DateTime::now());
        $this->uuid = new Uuid('58a35de9-eb6b-46b7-89ab-26c598304a67');
    }

    /**
     * @test
     */
    public function it_can_project_a_created_role(): void
    {
        $roleCreated = new RoleCreated(
            $this->uuid,
            'role_name'
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->uuid->toString(), 'role_name');

        $this->projector->applyRoleCreated($roleCreated, $this->domainMessage);
    }

    /**
     * @test
     */
    public function it_can_project_a_renamed_role(): void
    {
        $roleRenamed = new RoleRenamed(
            $this->uuid,
            'role_name'
        );

        $this->repository
            ->expects($this->once())
            ->method('updateName')
            ->with($this->uuid->toString(), 'role_name');

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
            ->with($this->uuid->toString());

        $this->projector->applyRoleDeleted($roleDeleted, $this->domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_update_constraint_on_constraint_created_event(): void
    {
        $constraintAdded = new ConstraintAdded(
            new Uuid('54c49e3e-d022-4c39-8ff9-dfc7f1f79bf2'),
            new Query('zipCode:3000')
        );
        $domainMessage = $this->createDomainMessage($constraintAdded);

        $this->repository->expects($this->once())
            ->method('updateConstraint')
            ->with($constraintAdded->getUuid()->toString(), $constraintAdded->getQuery()->toString());

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_update_constraint_on_constraint_updated_event(): void
    {
        $constraintUpdated = new ConstraintUpdated(
            new Uuid('1acb8dcc-90cd-4b3e-af40-2db11af15106'),
            new Query('zipCode:3000')
        );
        $domainMessage = $this->createDomainMessage($constraintUpdated);

        $this->repository->expects($this->once())
            ->method('updateConstraint')
            ->with($constraintUpdated->getUuid()->toString(), $constraintUpdated->getQuery()->toString());

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_calls_update_constraint_on_constraint_removed_event(): void
    {
        $constraintRemoved = new ConstraintRemoved(new Uuid('b2d63a70-1796-452f-9779-ca759327d975'));
        $domainMessage = $this->createDomainMessage($constraintRemoved);

        $this->repository->expects($this->once())
            ->method('updateConstraint')
            ->with($constraintRemoved->getUuid()->toString());

        $this->projector->handle($domainMessage);
    }

    private function createDomainMessage(Serializable $payload): DomainMessage
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
