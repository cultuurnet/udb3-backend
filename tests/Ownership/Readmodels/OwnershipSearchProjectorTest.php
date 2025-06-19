<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OwnershipSearchProjectorTest extends TestCase
{
    private OwnershipSearchRepository&MockObject $ownershipSearchRepository;

    private OwnershipSearchProjector $ownershipSearchProjector;

    public function setUp(): void
    {
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);
        $this->ownershipSearchProjector = new OwnershipSearchProjector($this->ownershipSearchRepository);
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
     * @param OwnershipRequested|OwnershipApproved|OwnershipRejected|OwnershipDeleted $event
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
