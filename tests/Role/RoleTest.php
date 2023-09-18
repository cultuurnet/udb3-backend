<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;

class RoleTest extends AggregateRootScenarioTestCase
{
    private UUID $uuid;

    private string $name;

    private Permission $permission;

    private Query $query;

    private Query $updatedQuery;

    private RoleCreated $roleCreated;

    private PermissionAdded $permissionAdded;

    private PermissionRemoved $permissionRemoved;

    private ConstraintAdded $constraintAdded;

    private ConstraintUpdated $constraintUpdated;

    private ConstraintRemoved $constraintRemoved;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid = new UUID('05957bcc-fcfc-422b-94f7-d0458f4016e4');
        $this->name = 'roleName';
        $this->permission = Permission::aanbodBewerken();
        $this->query = new Query('category_flandersregion_name:"Regio Aalst"');
        $this->updatedQuery = new Query('category_flandersregion_name:"Regio Brussel"');

        $this->roleCreated = new RoleCreated(
            $this->uuid,
            $this->name
        );

        $this->permissionAdded = new PermissionAdded(
            $this->uuid,
            $this->permission
        );

        $this->permissionRemoved = new PermissionRemoved(
            $this->uuid,
            $this->permission
        );

        $this->constraintAdded = new ConstraintAdded(
            $this->uuid,
            $this->query
        );

        $this->constraintUpdated = new ConstraintUpdated(
            $this->uuid,
            $this->updatedQuery
        );

        $this->constraintRemoved = new ConstraintRemoved(
            $this->uuid
        );
    }

    protected function getAggregateRootClass(): string
    {
        return Role::class;
    }

    /**
     * @test
     */
    public function it_can_create_a_new_role(): void
    {
        $this->scenario
            ->when(function () {
                return Role::create(
                    $this->uuid,
                    $this->name
                );
            })
            ->then([$this->roleCreated]);
    }

    /**
     * @test
     */
    public function it_can_rename_a_role(): void
    {
        $uuid = $this->uuid;
        $name = $this->name;

        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(function (Role $role) use ($uuid, $name): void {
                $role->rename(
                    $uuid,
                    $name
                );
            })
            ->then([new RoleRenamed($this->uuid, $this->name)]);
    }

    /**
     * @test
     */
    public function it_can_add_a_permission(): void
    {
        $uuid = $this->uuid;
        $permission = $this->permission;

        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(function (Role $role) use ($uuid, $permission): void {
                $role->addPermission(
                    $uuid,
                    $permission
                );
            })
            ->then([$this->permissionAdded]);
    }

    /**
     * @test
     */
    public function it_cannot_add_a_permission_that_has_already_been_added(): void
    {
        $uuid = $this->uuid;
        $permission = $this->permission;

        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated, new PermissionAdded($this->uuid, Permission::aanbodBewerken())])
            ->when(function (Role $role) use ($uuid, $permission): void {
                $role->addPermission(
                    $uuid,
                    $permission
                );
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_remove_a_permission(): void
    {
        $uuid = $this->uuid;
        $permission = $this->permission;

        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated, $this->permissionAdded])
            ->when(function (Role $role) use ($uuid, $permission): void {
                $role->removePermission(
                    $uuid,
                    $permission
                );
            })
            ->then([$this->permissionRemoved]);
    }

    /**
     * @test
     */
    public function it_cannot_remove_a_permission_that_does_not_exist_on_the_role(): void
    {
        $uuid = $this->uuid;
        $permission = $this->permission;

        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(function (Role $role) use ($uuid, $permission): void {
                $role->removePermission(
                    $uuid,
                    $permission
                );
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_add_a_constraint(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(function (Role $role): void {
                $role->addConstraint($this->query);
            })
            ->then([$this->constraintAdded]);
    }

    /**
     * @test
     */
    public function it_can_not_add_a_constraint_twice(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
            ])
            ->when(function (Role $role): void {
                $role->addConstraint($this->query);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_an_empty_constraint(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(function (Role $role): void {
                $role->updateConstraint($this->query);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_update_a_constraint(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
            ])
            ->when(function (Role $role): void {
                $role->updateConstraint($this->updatedQuery);
            })
            ->then([$this->constraintUpdated]);
    }

    /**
     * @test
     */
    public function it_does_not_update_a_constraint_with_same_query(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
                $this->constraintUpdated,
            ])
            ->when(function (Role $role): void {
                $role->updateConstraint($this->updatedQuery);
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_remove_a_constraint(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
            ])
            ->when(function (Role $role): void {
                $role->removeConstraint();
            })
            ->then([$this->constraintRemoved]);
    }

    /**
     * @test
     */
    public function it_does_not_remove_a_constraint_when_there_is_none(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([
                $this->roleCreated,
            ])
            ->when(function (Role $role): void {
                $role->removeConstraint();
            })
            ->then([]);
    }
}
