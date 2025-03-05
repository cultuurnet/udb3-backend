<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendToCreatorOfOrganisationTest extends TestCase
{
    /**
     * @var UserIdentityResolver&MockObject
     */
    private $identityResolver;
    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;
    /**
     * @var DocumentRepository&MockObject
     */
    private $organizerRepository;
    private SendToCreatorOfOrganisation $strategy;
    private OwnershipItem $ownershipItem;
    private string $itemId;
    private string $creator;
    private string $ownerId;

    protected function setUp(): void
    {
        $this->itemId = Uuid::uuid4()->toString();
        $this->creator = Uuid::uuid4()->toString();
        $this->ownerId = Uuid::uuid4()->toString();
        $this->organizerRepository = $this->createMock(DocumentRepository::class);

        $this->ownershipItem = new OwnershipItem(
            Uuid::uuid4()->toString(),
            $this->itemId,
            'organizer',
            $this->ownerId,
            'requested'
        );

        $this->identityResolver = $this->createMock(UserIdentityResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->strategy = new SendToCreatorOfOrganisation($this->identityResolver, $this->organizerRepository, $this->logger);
    }

    /** @test */
    public function it_gets_owner_details(): void
    {
        $ownerDetails = new UserIdentityDetails(
            $this->ownerId,
            'Grote smurf',
            'grotesmurf@publiq.be'
        );
        $this->identityResolver->method('getUserById')->with($this->creator)->willReturn($ownerDetails);

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($this->itemId)
            ->willReturn(new JsonDocument($this->ownerId, json_encode(['creator' => $this->creator], JSON_THROW_ON_ERROR)));

        $recipients = $this->strategy->getRecipients($this->ownershipItem);

        $this->assertCount(1, $recipients);
        $this->assertSame($ownerDetails, $recipients[0]);
    }

    /** @test */
    public function it_logs_warning_and_returns_nothing_when_owner_not_found(): void
    {
        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($this->itemId)
            ->willReturn(new JsonDocument($this->ownerId, json_encode(['creator' => $this->creator], JSON_THROW_ON_ERROR)));

        $this->identityResolver->method('getUserById')->with($this->creator)->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Could not load owner details for ' . $this->creator));

        $this->assertEmpty($this->strategy->getRecipients($this->ownershipItem));
    }
}
