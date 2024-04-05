<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use CultuurNet\UDB3\Role\ReadModel\Search\RepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Search\Results;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactory;

class OwnershipPermissionProjectorTest extends TestCase
{
    private TraceableAuthorizedCommandBus $commandBus;

    /** @var OwnershipSearchRepository&MockObject */
    private $ownershipSearchRepository;

    /** @var UuidFactory&MockObject */
    private $uuidFactory;

    /** @var RepositoryInterface&MockObject */
    private $roleSearchRepository;

    private OwnershipPermissionProjector $ownershipPermissionProjector;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableAuthorizedCommandBus(new TraceableCommandBus());
        $this->commandBus->record();

        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->uuidFactory = $this->createMock(UuidFactory::class);

        $this->roleSearchRepository = $this->createMock(RepositoryInterface::class);

        $this->ownershipPermissionProjector = new OwnershipPermissionProjector(
            $this->commandBus,
            $this->ownershipSearchRepository,
            $this->uuidFactory,
            $this->roleSearchRepository
        );
    }

    /**
     * @test
     */
    public function it_handles_ownership_approved(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::fromString('2024-02-19T14:15:16Z'));

        $ownershipRequested = new OwnershipApproved($ownershipId);

        $domainMessage = new DomainMessage(
            $ownershipId,
            0,
            new Metadata(),
            $ownershipRequested,
            $recordedOn->toBroadwayDateTime()
        );

        $this->ownershipSearchRepository->expects($this->once())
            ->method('getById')
            ->with($ownershipId)
            ->willReturn(
                new OwnershipItem(
                    $ownershipId,
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b'
                )
            );

        $organizationRoleId = new UUID('8d17cffe-6f28-459c-8627-1f6345f8b296');
        $eventRoleId = new UUID('e8f8e2bc-2666-47f3-9290-ae3e7229a2c1');
        $this->uuidFactory->expects($this->exactly(2))
            ->method('uuid4')
            ->willReturnCallback(
                function () use ($organizationRoleId, $eventRoleId) {
                    static $count = 0;
                    $count++;
                    return $count === 1 ? $organizationRoleId : $eventRoleId;
                }
            );

        $this->ownershipPermissionProjector->handle($domainMessage);

        $this->assertEquals(
            [
                new CreateRole(
                    $organizationRoleId,
                    'Ownership ' . $ownershipId
                ),
                new AddUser(
                    $organizationRoleId,
                    'auth0|63e22626e39a8ca1264bd29b'
                ),
                new AddConstraint(
                    $organizationRoleId,
                    new Query('id:9e68dafc-01d8-4c1c-9612-599c918b981d')
                ),
                new AddPermission(
                    $organizationRoleId,
                    Permission::organisatiesBewerken()
                ),
                new CreateRole(
                    $eventRoleId,
                    'Ownership ' . $ownershipId
                ),
                new AddUser(
                    $eventRoleId,
                    'auth0|63e22626e39a8ca1264bd29b'
                ),
                new AddConstraint(
                    $eventRoleId,
                    new Query('organizer.id:9e68dafc-01d8-4c1c-9612-599c918b981d')
                ),
                new AddPermission(
                    $eventRoleId,
                    Permission::aanbodBewerken()
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_handles_ownership_deleted(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::fromString('2024-02-19T14:15:16Z'));

        $ownershipRequested = new OwnershipDeleted($ownershipId);

        $domainMessage = new DomainMessage(
            $ownershipId,
            0,
            new Metadata(),
            $ownershipRequested,
            $recordedOn->toBroadwayDateTime()
        );

        $roleId = '8d17cffe-6f28-459c-8627-1f6345f8b296';
        $this->roleSearchRepository->expects($this->once())
            ->method('search')
            ->with('Ownership ' . $ownershipId)
            ->willReturn(
                new Results(
                    1,
                    [
                        [
                            'uuid' => $roleId,
                            'name' => 'Ownership ' . $ownershipId,
                        ],
                    ],
                    1
                )
            );

        $this->ownershipPermissionProjector->handle($domainMessage);

        $this->assertEquals(
            [
                new DeleteRole(new UUID($roleId)),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
