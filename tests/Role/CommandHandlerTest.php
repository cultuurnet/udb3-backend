<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role;

use Broadway\CommandHandling\CommandHandler as BroadwayCommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddLabel;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use CultuurNet\UDB3\Role\Commands\RemoveConstraint;
use CultuurNet\UDB3\Role\Commands\RemoveLabel;
use CultuurNet\UDB3\Role\Commands\RemovePermission;
use CultuurNet\UDB3\Role\Commands\RemoveUser;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use CultuurNet\UDB3\Role\Commands\UpdateConstraint;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;

class CommandHandlerTest extends CommandHandlerScenarioTestCase
{
    private Uuid $uuid;

    private string $name;

    private Permission $permission;

    private Query $query;

    private Query $updatedQuery;

    private Uuid $labelId;

    private RoleCreated $roleCreated;

    private RoleRenamed $roleRenamed;

    private PermissionAdded $permissionAdded;

    private PermissionRemoved $permissionRemoved;

    private ConstraintAdded $constraintAdded;

    private ConstraintUpdated $constraintUpdated;

    private ConstraintRemoved $constraintRemoved;

    private LabelAdded $labelAdded;

    private LabelRemoved $labelRemoved;

    private RoleDeleted $roleDeleted;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid = new Uuid('708bae44-9788-4318-8f19-1087da9e5814');
        $this->name = 'labelName';
        $this->permission = Permission::aanbodBewerken();
        $this->query = new Query('category_flandersregion_name:"Regio Aalst"');
        $this->updatedQuery = new Query('category_flandersregion_name:"Regio Brussel"');
        $this->labelId = new Uuid('9335212c-54bd-466f-9772-4626b799927b');

        $this->roleCreated = new RoleCreated(
            $this->uuid,
            $this->name
        );

        $this->roleRenamed = new RoleRenamed(
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
            $this->uuid,
        );

        $this->labelAdded = new LabelAdded(
            $this->uuid,
            $this->labelId
        );

        $this->labelRemoved = new LabelRemoved(
            $this->uuid,
            $this->labelId
        );

        $this->roleDeleted = new RoleDeleted(
            $this->uuid
        );
    }

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): BroadwayCommandHandler {
        return new CommandHandler(new RoleRepository(
            $eventStore,
            $eventBus
        ));
    }

    /**
     * @test
     */
    public function it_handles_create(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([])
            ->when(new CreateRole(
                $this->uuid,
                $this->name
            ))
            ->then([$this->roleCreated]);
    }

    /**
     * @test
     */
    public function it_handles_rename(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(new RenameRole(
                $this->uuid,
                $this->name
            ))
            ->then([$this->roleRenamed]);
    }

    /**
     * @test
     */
    public function it_handles_addPermission(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(new AddPermission(
                $this->uuid,
                $this->permission
            ))
            ->then([$this->permissionAdded]);
    }

    /**
     * @test
     */
    public function it_handles_removePermission(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated, new PermissionAdded($this->uuid, $this->permission)])
            ->when(new RemovePermission(
                $this->uuid,
                $this->permission
            ))
            ->then([$this->permissionRemoved]);
    }

    /**
     * @test
     */
    public function it_can_add_and_remove_users(): void
    {
        $userId = '123456';

        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])

            // Add a user.
            ->when(
                new AddUser(
                    $this->uuid,
                    $userId
                )
            )
            ->then(
                [
                    new UserAdded(
                        $this->uuid,
                        $userId
                    ),
                ]
            )

            // Adding the same user should not result in any new events.
            ->when(
                new AddUser(
                    $this->uuid,
                    $userId
                )
            )
            ->then(
                []
            )

            // Remove the user.
            ->when(
                new RemoveUser(
                    $this->uuid,
                    $userId
                )
            )
            ->then(
                [
                    new UserRemoved(
                        $this->uuid,
                        $userId
                    ),
                ]
            )

            // Removing the user again should not result in any new events.
            ->when(
                new RemoveUser(
                    $this->uuid,
                    $userId
                )
            )
            ->then(
                []
            )

            // Removing a user that was never added to the role should not
            // result in any new events.
            ->when(
                new RemoveUser(
                    $this->uuid,
                    'user-that-was-never-added'
                )
            )
            ->then(
                []
            );
    }

    /**
     * @test
     */
    public function it_handles_addConstraint(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(new AddConstraint(
                $this->uuid,
                $this->query
            ))
            ->then([$this->constraintAdded]);
    }

    /**
     * @test
     */
    public function it_handles_updateConstraint(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated, $this->constraintAdded])
            ->when(new UpdateConstraint(
                $this->uuid,
                $this->updatedQuery
            ))
            ->then([$this->constraintUpdated]);
    }

    /**
     * @test
     */
    public function it_handles_removeConstraint(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated, $this->constraintAdded])
            ->when(new RemoveConstraint($this->uuid))
            ->then([$this->constraintRemoved]);
    }

    /**
     * @test
     */
    public function it_handles_addLabel(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(
                new AddLabel(
                    $this->uuid,
                    $this->labelId
                )
            )
            ->then([$this->labelAdded]);
    }

    /**
     * @test
     */
    public function it_handles_removeLabel(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated, $this->labelAdded])
            ->when(
                new RemoveLabel(
                    $this->uuid,
                    $this->labelId
                )
            )
            ->then([$this->labelRemoved]);
    }

    /**
     * @test
     */
    public function it_handles_deleteRole_by_deleting_the_role(): void
    {
        $this->scenario
            ->withAggregateId($this->uuid->toString())
            ->given([$this->roleCreated])
            ->when(new DeleteRole(
                $this->uuid
            ))
            ->then([$this->roleDeleted]);
    }
}
