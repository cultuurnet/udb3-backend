<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendToOwnerOfOwnershipTest extends TestCase
{
    private MockObject $identityResolver;
    private MockObject $logger;
    private SendToOwnerOfOwnership $strategy;
    private OwnershipItem $ownershipItem;
    private string $ownerId;

    protected function setUp(): void
    {
        $this->ownerId = Uuid::uuid4()->toString();
        $this->ownershipItem = new OwnershipItem(
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            'organizer',
            $this->ownerId,
            'requested'
        );

        $this->identityResolver = $this->createMock(UserIdentityResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->strategy = new SendToOwnerOfOwnership($this->identityResolver, $this->logger);
    }

    /** @test */
    public function it_gets_owner_details(): void
    {
        $ownerDetails = new UserIdentityDetails(
            $this->ownerId,
            'Grote smurf',
            'grotesmurf@publiq.be'
        );
        $this->identityResolver->method('getUserById')->with($this->ownerId)->willReturn($ownerDetails);

        $recipients = $this->strategy->getRecipients($this->ownershipItem);

        $this->assertCount(1, $recipients);
        $this->assertSame($ownerDetails, $recipients[0]);
    }

    /** @test */
    public function it_logs_warning_and_returns_nothing_when_owner_not_found(): void
    {
        $this->identityResolver->method('getUserById')->with($this->ownerId)->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Could not load owner details for ' . $this->ownerId));

        $this->assertEmpty($this->strategy->getRecipients($this->ownershipItem));
    }
}
