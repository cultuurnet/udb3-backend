<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class RoleTest extends AggregateRootScenarioTestCase
{
    private UUID $uuid;

    private StringLiteral $name;

    private Permission $permission;

    private Query $query;

    private Query $updatedQuery;

    private SapiVersion $sapiVersion;

    private RoleCreated $roleCreated;

    private PermissionAdded $permissionAdded;

    private PermissionRemoved $permissionRemoved;

    private ConstraintAdded $constraintAdded;

    private ConstraintUpdated $constraintUpdated;

    private ConstraintRemoved $constraintRemoved;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid = new UUID();
        $this->name = new StringLiteral('roleName');
        $this->permission = Permission::AANBOD_BEWERKEN();
        $this->query = new Query('category_flandersregion_name:"Regio Aalst"');
        $this->updatedQuery = new Query('category_flandersregion_name:"Regio Brussel"');
        $this->sapiVersion = SapiVersion::V3();

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
            ->withAggregateId($this->uuid)
            ->given([$this->roleCreated])
            ->when(function (Role $role) use ($uuid, $name) {
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
            ->withAggregateId($this->uuid)
            ->given([$this->roleCreated])
            ->when(function (Role $role) use ($uuid, $permission) {
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
            ->withAggregateId($this->uuid)
            ->given([$this->roleCreated, new PermissionAdded($this->uuid, Permission::AANBOD_BEWERKEN())])
            ->when(function (Role $role) use ($uuid, $permission) {
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
            ->withAggregateId($this->uuid)
            ->given([$this->roleCreated, $this->permissionAdded])
            ->when(function (Role $role) use ($uuid, $permission) {
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
            ->withAggregateId($this->uuid)
            ->given([$this->roleCreated])
            ->when(function (Role $role) use ($uuid, $permission) {
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
            ->withAggregateId($this->uuid)
            ->given([$this->roleCreated])
            ->when(function (Role $role) {
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
            ->withAggregateId($this->uuid)
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
            ])
            ->when(function (Role $role) {
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
            ->withAggregateId($this->uuid)
            ->given([$this->roleCreated])
            ->when(function (Role $role) {
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
            ->withAggregateId($this->uuid)
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
            ])
            ->when(function (Role $role) {
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
            ->withAggregateId($this->uuid)
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
                $this->constraintUpdated,
            ])
            ->when(function (Role $role) {
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
            ->withAggregateId($this->uuid)
            ->given([
                $this->roleCreated,
                $this->constraintAdded,
            ])
            ->when(function (Role $role) {
                $role->removeConstraint(
                    $this->sapiVersion
                );
            })
            ->then([$this->constraintRemoved]);
    }

    /**
     * @test
     */
    public function it_does_not_remove_a_constraint_when_there_is_none(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid)
            ->given([
                $this->roleCreated,
            ])
            ->when(function (Role $role) {
                $role->removeConstraint(
                    $this->sapiVersion
                );
            })
            ->then([]);
    }
}
