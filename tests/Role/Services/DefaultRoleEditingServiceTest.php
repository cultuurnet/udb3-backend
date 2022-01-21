<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Services;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddLabel;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use CultuurNet\UDB3\Role\Commands\RemoveConstraint;
use CultuurNet\UDB3\Role\Commands\RemoveLabel;
use CultuurNet\UDB3\Role\Commands\RemovePermission;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use CultuurNet\UDB3\Role\Commands\UpdateConstraint;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\RoleRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class DefaultRoleEditingServiceTest extends TestCase
{
    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;


    protected TraceableEventStore $eventStore;

    private UUID $uuid;

    private UUID $labelId;

    private CreateRole $createRole;

    private RenameRole $renameRole;

    private AddPermission $addPermission;

    private RemovePermission $removePermission;

    private AddLabel $addLabel;

    private RemoveLabel $removeLabel;

    private DeleteRole $deleteRole;

    private DefaultRoleEditingService $roleEditingService;

    public function setUp(): void
    {
        $this->uuid = new UUID('9196cb78-4381-11e6-beb8-9e71128cae77');

        $this->commandBus = $this->createMock(CommandBus::class);
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->labelId = new UUID();

        $this->eventStore = new TraceableEventStore(new InMemoryEventStore());

        $writeRepository = new RoleRepository(
            $this->eventStore,
            new SimpleEventBus()
        );

        $this->createRole = new CreateRole(
            $this->uuid,
            new StringLiteral('roleName')
        );

        $this->renameRole = new RenameRole(
            $this->uuid,
            new StringLiteral('new roleName')
        );

        $this->addPermission = new AddPermission(
            $this->uuid,
            Permission::aanbodBewerken()
        );

        $this->removePermission = new RemovePermission(
            $this->uuid,
            Permission::aanbodBewerken()
        );

        $this->addLabel = new AddLabel(
            $this->uuid,
            $this->labelId
        );

        $this->removeLabel = new RemoveLabel(
            $this->uuid,
            $this->labelId
        );

        $this->deleteRole = new DeleteRole(
            $this->uuid
        );

        $uuidGenerator->method('generate')
            ->willReturn('9196cb78-4381-11e6-beb8-9e71128cae77');

        $this->roleEditingService = new DefaultRoleEditingService(
            $this->commandBus,
            $uuidGenerator,
            $writeRepository
        );
    }

    /**
     * @test
     */
    public function it_can_create_a_role(): void
    {
        $this->eventStore->trace();

        $roleId = $this->roleEditingService->create(
            new StringLiteral('roleName')
        );

        $expectedUuid = $this->uuid;

        $this->assertEquals(
            [
                new RoleCreated(
                    $this->uuid,
                    new StringLiteral('roleName')
                ),
            ],
            $this->eventStore->getEvents()
        );

        $this->assertEquals($expectedUuid, $roleId);
    }

    /**
     * @test
     */
    public function it_can_rename_a_role(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->renameRole);

        $this->roleEditingService->rename(
            $this->uuid,
            new StringLiteral('new roleName')
        );
    }

    /**
     * @test
     */
    public function it_can_add_a_constraint(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new AddConstraint($this->uuid, new Query('test query')));

        $this->roleEditingService->addConstraint(
            $this->uuid,
            new Query('test query')
        );
    }

    /**
     * @test
     */
    public function it_can_update_a_constraint(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new UpdateConstraint($this->uuid, new Query('test query')));

        $this->roleEditingService->updateConstraint(
            $this->uuid,
            new Query('test query')
        );
    }

    /**
     * @test
     */
    public function it_can_remove_a_constraint(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new RemoveConstraint($this->uuid));

        $this->roleEditingService->removeConstraint(
            $this->uuid,
        );
    }

    /**
     * @test
     */
    public function it_can_add_a_permission(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->addPermission);

        $this->roleEditingService->addPermission(
            $this->uuid,
            Permission::aanbodBewerken()
        );
    }

    /**
     * @test
     */
    public function it_can_remove_a_permission(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->removePermission);

        $this->roleEditingService->removePermission(
            $this->uuid,
            Permission::aanbodBewerken()
        );
    }

    /**
     * @test
     */
    public function it_can_add_a_label(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->addLabel);

        $this->roleEditingService->addLabel(
            $this->uuid,
            $this->labelId
        );
    }

    /**
     * @test
     */
    public function it_can_remove_a_label(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->removeLabel);

        $this->roleEditingService->removeLabel(
            $this->uuid,
            $this->labelId
        );
    }

    /**
     * @test
     */
    public function it_can_delete_a_role(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->deleteRole);

        $this->roleEditingService->delete(
            $this->uuid
        );
    }
}
