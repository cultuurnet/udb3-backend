<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SendToCreatorOfOrganisationTest extends TestCase
{
    /**
     * @var UserIdentityResolver&MockObject
     */
    private $identityResolver;

    /**
     * @var DocumentRepository&MockObject
     */
    private $organizerRepository;
    private SendToCreatorOfOrganisation $strategy;
    private OwnershipItem $ownershipItem;
    private string $itemId;
    private string $creator;

    protected function setUp(): void
    {
        $this->itemId = Uuid::uuid4()->toString();
        $this->creator = Uuid::uuid4()->toString();
        $ownerId = Uuid::uuid4()->toString();
        $this->organizerRepository = $this->createMock(DocumentRepository::class);

        $this->ownershipItem = new OwnershipItem(
            Uuid::uuid4()->toString(),
            $this->itemId,
            'organizer',
            $ownerId,
            'requested'
        );

        $this->identityResolver = $this->createMock(UserIdentityResolver::class);
        $this->strategy = new SendToCreatorOfOrganisation($this->identityResolver, $this->organizerRepository);
    }

    /** @test */
    public function it_gets_creator_details(): void
    {
        $ownerDetails = new UserIdentityDetails(
            $this->creator,
            'Grote smurf',
            'grotesmurf@publiq.be'
        );
        $this->identityResolver->method('getUserById')->with($this->creator)->willReturn($ownerDetails);

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($this->itemId)
            ->willReturn(new JsonDocument($this->itemId, json_encode(['creator' => $this->creator], JSON_THROW_ON_ERROR)));

        $recipients = $this->strategy->getRecipients($this->ownershipItem);

        $this->assertCount(1, $recipients);
        $this->assertSame((new Recipients($ownerDetails))->getRecipients(), $recipients->getRecipients());
    }

    /** @test */
    public function get_recipients_handles_exception_gracefully(): void
    {
        $ownerDetails = new UserIdentityDetails(
            $this->creator,
            'Grote smurf',
            'grotesmurf@publiq.be'
        );
        $this->identityResolver->method('getUserById')->with($this->creator)->willReturn($ownerDetails);

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($this->itemId)
            ->willThrowException(new DocumentDoesNotExist());

        $recipients = $this->strategy->getRecipients($this->ownershipItem);

        $this->assertCount(0, $recipients);
    }
}
