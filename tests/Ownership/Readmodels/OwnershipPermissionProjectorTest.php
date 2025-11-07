<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\FixedUuidFactory;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Readmodels\Name\ItemNameResolver;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\RemoveUser;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OwnershipPermissionProjectorTest extends TestCase
{
    private const ROLE_ID = '8d17cffe-6f28-459c-8627-1f6345f8b296';
    private TraceableAuthorizedCommandBus $commandBus;

    private OwnershipSearchRepository&MockObject $ownershipSearchRepository;

    private OwnershipPermissionProjector $ownershipPermissionProjector;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableAuthorizedCommandBus(new TraceableCommandBus());
        $this->commandBus->record();

        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $itemNameResolver = $this->createMock(ItemNameResolver::class);
        $itemNameResolver->expects($this->any())
            ->method('resolve')
            ->with('9e68dafc-01d8-4c1c-9612-599c918b981d')
            ->willReturn('publiq vzw');

        $this->ownershipPermissionProjector = new OwnershipPermissionProjector(
            $this->commandBus,
            $this->ownershipSearchRepository,
            new FixedUuidFactory(new Uuid(self::ROLE_ID)),
            $itemNameResolver
        );
    }

    /**
     * @test
     */
    public function it_handles_ownership_approved(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $roleId = new Uuid(self::ROLE_ID);
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
                    $itemId,
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    OwnershipState::requested()->toString()
                )
            );

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(
                new SearchQuery(
                    [
                        new SearchParameter('itemId', $itemId),
                    ],
                    0,
                    1
                )
            )
            ->willReturn(new OwnershipItemCollection());

        $this->ownershipSearchRepository->expects($this->once())
            ->method('updateRoleId')
            ->with($ownershipId, $roleId);

        $this->ownershipPermissionProjector->handle($domainMessage);

        $this->assertEquals(
            [
                new CreateRole(
                    $roleId,
                    'Beheerders organisatie publiq vzw'
                ),
                new AddUser(
                    $roleId,
                    'auth0|63e22626e39a8ca1264bd29b'
                ),
                new AddConstraint(
                    $roleId,
                    new Query('(id:9e68dafc-01d8-4c1c-9612-599c918b981d OR (organizer.id:9e68dafc-01d8-4c1c-9612-599c918b981d AND _type:event))')
                ),
                new AddPermission(
                    $roleId,
                    Permission::organisatiesBewerken()
                ),
                new AddPermission(
                    $roleId,
                    Permission::aanbodBewerken()
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_handles_ownership_approved_when_role_already_exists_for_item(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $roleId = new Uuid(self::ROLE_ID);
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
                    $itemId,
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    OwnershipState::requested()->toString()
                )
            );

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(
                new SearchQuery(
                    [
                        new SearchParameter('itemId', $itemId),
                    ],
                    0,
                    1
                )
            )
            ->willReturn(
                new OwnershipItemCollection(
                    (new OwnershipItem(
                        $ownershipId,
                        $itemId,
                        'organizer',
                        'auth0|47e22626f39a8ca1264bd33d',
                        OwnershipState::requested()->toString()
                    ))->withRoleId($roleId)
                )
            );

        $this->ownershipSearchRepository->expects($this->once())
            ->method('updateRoleId')
            ->with($ownershipId, $roleId);

        $this->ownershipPermissionProjector->handle($domainMessage);

        $this->assertEquals(
            [
                new AddUser(
                    $roleId,
                    'auth0|63e22626e39a8ca1264bd29b'
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
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::fromString('2024-02-19T14:15:16Z'));

        $ownershipRequested = new OwnershipDeleted($ownershipId);

        $domainMessage = new DomainMessage(
            $ownershipId,
            0,
            new Metadata(),
            $ownershipRequested,
            $recordedOn->toBroadwayDateTime()
        );

        $roleId = self::ROLE_ID;

        $this->ownershipSearchRepository->expects($this->once())
            ->method('getById')
            ->with($ownershipId)
            ->willReturn(
                (new OwnershipItem(
                    $ownershipId,
                    $itemId,
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    OwnershipState::requested()->toString()
                ))->withRoleId(new Uuid($roleId))
            );

        $this->ownershipSearchRepository->expects($this->once())
            ->method('updateRoleId')
            ->with($ownershipId, null);

        $this->ownershipPermissionProjector->handle($domainMessage);

        $this->assertEquals(
            [
                new RemoveUser(
                    new Uuid($roleId),
                    'auth0|63e22626e39a8ca1264bd29b'
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
