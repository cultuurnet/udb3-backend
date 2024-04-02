<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\RecordedOn;
use PHPUnit\Framework\TestCase;

class OwnershipLDProjectorTest extends TestCase
{
    private InMemoryDocumentRepository $ownershipRepository;

    private OwnershipLDProjector $ownershipLDProjector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownershipRepository = new InMemoryDocumentRepository();

        $this->ownershipLDProjector = new OwnershipLDProjector($this->ownershipRepository);
    }

    /**
     * @test
     */
    public function it_handles_ownership_requested(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::fromString('2024-02-19T14:15:16Z'));

        $ownershipRequested = new OwnershipRequested(
            $ownershipId,
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            'google-oauth2|102486314601596809843'
        );

        $domainMessage = new DomainMessage(
            $ownershipId,
            0,
            new Metadata(),
            $ownershipRequested,
            $recordedOn->toBroadwayDateTime()
        );

        $this->ownershipLDProjector->handle($domainMessage);

        $jsonDocument = $this->ownershipRepository->fetch($ownershipId);

        $this->assertEquals(
            $this->createOwnershipJsonDocument(
                $ownershipId,
                $recordedOn,
                OwnershipState::requested()
            ),
            $jsonDocument
        );
    }

    /**
     * @test
     */
    public function it_handles_ownership_approved(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::fromString('2024-02-19T14:15:16Z'));

        $this->ownershipRepository->save(
            $this->createOwnershipJsonDocument(
                $ownershipId,
                $recordedOn,
                OwnershipState::requested()
            )
        );

        $ownershipApproved = new OwnershipApproved($ownershipId);

        $domainMessage = new DomainMessage(
            $ownershipId,
            0,
            new Metadata(),
            $ownershipApproved,
            $recordedOn->toBroadwayDateTime()
        );

        $this->ownershipLDProjector->handle($domainMessage);

        $jsonDocument = $this->ownershipRepository->fetch($ownershipId);

        $this->assertEquals(
            $this->createOwnershipJsonDocument(
                $ownershipId,
                $recordedOn,
                OwnershipState::approved()
            ),
            $jsonDocument
        );
    }

    /**
     * @test
     */
    public function it_handles_ownership_rejected(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::fromString('2024-02-19T14:15:16Z'));

        $this->ownershipRepository->save(
            $this->createOwnershipJsonDocument(
                $ownershipId,
                $recordedOn,
                OwnershipState::requested()
            )
        );

        $ownershipRejected = new OwnershipRejected($ownershipId, );

        $domainMessage = new DomainMessage(
            $ownershipId,
            0,
            new Metadata(),
            $ownershipRejected,
            $recordedOn->toBroadwayDateTime()
        );

        $this->ownershipLDProjector->handle($domainMessage);

        $jsonDocument = $this->ownershipRepository->fetch($ownershipId);

        $this->assertEquals(
            $this->createOwnershipJsonDocument(
                $ownershipId,
                $recordedOn,
                OwnershipState::rejected()
            ),
            $jsonDocument
        );
    }

    private function createOwnershipJsonDocument(
        string $ownershipId,
        RecordedOn $recordedOn,
        OwnershipState $state
    ): JsonDocument {
        $jsonLD = new \stdClass();
        $jsonLD->{'id'} = $ownershipId;
        $jsonLD->{'itemId'} = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $jsonLD->{'itemType'} = 'organizer';
        $jsonLD->{'ownerId'} = 'auth0|63e22626e39a8ca1264bd29b';
        $jsonLD->{'requesterId'} = 'google-oauth2|102486314601596809843';
        $jsonLD->{'state'} = $state->toString();
        $jsonLD->{'created'} = $recordedOn->toString();
        $jsonLD->{'modified'} = $recordedOn->toString();

        return (new JsonDocument($ownershipId))->withBody($jsonLD);
    }
}
