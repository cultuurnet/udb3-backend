<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\ReadModel\Search\SearchByRoleIdAndPermissions;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OwnershipSearchProjectorTest extends TestCase
{
    /** @var OwnershipSearchRepository & MockObject */
    private $ownershipSearchRepository;

    /** @var DocumentRepository & MockObject */
    private $organizerRepository;

    /** @var SearchByRoleIdAndPermissions & MockObject */
    private $searchByRoleIdAndPermissions;

    private OwnershipSearchProjector $ownershipSearchProjector;

    public function setUp(): void
    {
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);
        $this->organizerRepository = $this->createMock(DocumentRepository::class);
        $this->searchByRoleIdAndPermissions = $this->createMock(SearchByRoleIdAndPermissions::class);

        $this->ownershipSearchProjector = new OwnershipSearchProjector(
            $this->ownershipSearchRepository,
            $this->organizerRepository,
            $this->searchByRoleIdAndPermissions,
        );
    }

    /**
     * @test
     */
    public function it_handles_ownership_requested(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $ownershipRequested = new OwnershipRequested(
            $ownershipId,
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            'google-oauth2|102486314601596809843'
        );

        $ownershipItem = new OwnershipItem(
            $ownershipRequested->getId(),
            $ownershipRequested->getItemId(),
            $ownershipRequested->getItemType(),
            $ownershipRequested->getOwnerId(),
            OwnershipState::requested()->toString()
        );

        $this->ownershipSearchRepository->expects($this->once())
            ->method('save')
            ->with($ownershipItem);

        $this->ownershipSearchProjector->handle($this->createDomainMessage($ownershipRequested));
    }

    /**
     * @test
     */
    public function it_handles_ownership_approved(): void
    {
        $ownershipApproved = new OwnershipApproved('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e');

        $this->ownershipSearchRepository->expects($this->once())
            ->method('updateState')
            ->with(
                $ownershipApproved->getId(),
                OwnershipState::approved()
            );

        $this->ownershipSearchProjector->handle($this->createDomainMessage($ownershipApproved));
    }

    /**
     * @test
     */
    public function it_handles_ownership_rejected(): void
    {
        $ownershipRejected = new OwnershipRejected('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e');

        $this->ownershipSearchRepository->expects($this->once())
            ->method('updateState')
            ->with(
                $ownershipRejected->getId(),
                OwnershipState::rejected()
            );

        $this->ownershipSearchProjector->handle($this->createDomainMessage($ownershipRejected));
    }

    /**
     * @test
     */
    public function it_handles_ownership_deleted(): void
    {
        $ownershipDeleted = new OwnershipDeleted('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e');

        $this->ownershipSearchRepository->expects($this->once())
            ->method('updateState')
            ->with(
                $ownershipDeleted->getId(),
                OwnershipState::deleted()
            );

        $this->ownershipSearchProjector->handle($this->createDomainMessage($ownershipDeleted));
    }




    /**
     * @test
     */
    public function it_handles_constraint_added(): void
    {
        $organizerId = 'b90d7a0d-73c9-47d5-a0ae-ebf2f99d1f6a';
        $roleId = new Uuid('ea1e3f06-b3dd-428f-b205-09c376d0cf12');
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $userId1 = '177e737d-27ed-4156-ae86-57b87030ed02';
        $userId2 = '8452a083-bfe8-4cd3-bea8-19bb322d7fd1';

        $constraintAdded = new ConstraintAdded(
            $roleId,
            new Query('id:' . $organizerId)
        );

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(new JsonDocument($organizerId, json_encode(['name' => 'Publiq vzw'], JSON_THROW_ON_ERROR)));

        $this->searchByRoleIdAndPermissions->expects($this->once())
            ->method('findAllUsers')
            ->with($roleId, [Permission::organisatiesBewerken()->toString()])
            ->willReturn([$userId1, $userId2]);

        $this->ownershipSearchRepository->expects($this->exactly(2))
            ->method('doesUserForOrganisationExist')
            ->willReturnCallback(function ($organizerIdInput, $ownerIdInput) use ($organizerId, $userId1) {
                $this->assertEquals(new Uuid($organizerId), $organizerIdInput);
                return $ownerIdInput === $userId1;
            });

        $this->ownershipSearchRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($item) use ($organizerId, $userId2, $roleId) {
                return $item instanceof OwnershipItem
                    && $item->getItemId() === $organizerId
                    && $item->getOwnerId() === $userId2
                    && $item->getRoleId() === $roleId;
            }));

        $this->ownershipSearchProjector->handle($this->createDomainMessage($constraintAdded));
    }

    /**
     * @param OwnershipRequested|OwnershipApproved|OwnershipRejected|OwnershipDeleted|ConstraintAdded|ConstraintUpdated|ConstraintRemoved $event
     */
    private function createDomainMessage($event): DomainMessage
    {
        return new DomainMessage(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            0,
            new Metadata(),
            $event,
            DateTime::now()
        );
    }
}
