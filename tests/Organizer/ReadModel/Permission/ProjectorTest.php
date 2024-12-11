<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Organizer\Events\OwnerChanged;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectorTest extends TestCase
{
    /**
     * @var ResourceOwnerRepository&MockObject
     */
    private $repository;

    private Projector $projector;

    public function setUp(): void
    {
        $this->repository = $this->createMock(ResourceOwnerRepository::class);
        $userIdResolver = $this->createMock(CreatedByToUserIdResolverInterface::class);

        $this->projector = new Projector(
            $this->repository,
            $userIdResolver
        );
    }

    /**
     * @test
     */
    public function it_handles_owner_changed(): void
    {
        $organizerId = '9a18a42f-d80d-4784-8c34-8b8b36dd6080';
        $newOwnerId = '20656964-10cd-4ca7-85f2-997137479900';
        $ownerChanged = new OwnerChanged($organizerId, $newOwnerId);

        $domainMessage = DomainMessage::recordNow(
            $organizerId,
            1,
            new Metadata(['user_id' => Uuid::NIL]),
            $ownerChanged
        );

        $this->repository->expects($this->once())
            ->method('markResourceEditableByNewUser')
            ->with(
                $organizerId,
                $newOwnerId
            );

        $this->projector->handle($domainMessage);
    }
}
