<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendToOwnerOfOwnershipTest extends TestCase
{
    /**
     * @var UserIdentityResolver&MockObject
     */
    private $identityResolver;

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
        $this->strategy = new SendToOwnerOfOwnership($this->identityResolver);
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
        $this->assertSame((new Recipients($ownerDetails))->getRecipients(), $recipients->getRecipients());
    }
}
