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
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\TestCase;

class OwnershipLDProjectorTest extends TestCase
{
    private InMemoryDocumentRepository $ownershipRepository;

    private OwnershipLDProjector $ownershipLDProjector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownershipRepository = new InMemoryDocumentRepository();

        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->any())
            ->method('getUserById')
            ->willReturnCallback(
                function (string $userId): ?UserIdentityDetails {
                    if ($userId === 'auth0|63e22626e39a8ca1264bd29b') {
                        return new UserIdentityDetails($userId, 'dev', 'dev+e2e@publiq.be');
                    }
                    if ($userId === 'google-oauth2|102486314601596809843') {
                        return new UserIdentityDetails($userId, 'google', 'info@google.be');
                    }
                    return null;
                }
            );

        $this->ownershipLDProjector = new OwnershipLDProjector(
            $this->ownershipRepository,
            $userIdentityResolver
        );
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

        $ownershipRejected = new OwnershipRejected($ownershipId);

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

    /**
     * @test
     */
    public function it_handles_ownership_deleted(): void
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

        $ownershipRejected = new OwnershipDeleted($ownershipId);

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
                OwnershipState::deleted()
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
        $jsonLD->{'ownerEmail'} = 'dev+e2e@publiq.be';
        $jsonLD->{'requesterId'} = 'google-oauth2|102486314601596809843';
        $jsonLD->{'requesterEmail'} = 'info@google.be';
        $jsonLD->{'state'} = $state->toString();
        $jsonLD->{'created'} = $recordedOn->toString();
        $jsonLD->{'modified'} = $recordedOn->toString();

        return (new JsonDocument($ownershipId))->withBody($jsonLD);
    }
}
