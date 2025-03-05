<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\DBALOwnershipSearchRepository;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SendToOwnersOfOrganisationTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var UserIdentityResolver|MockObject
     */
    private $identityResolver;
    private SendToOwnersOfOrganisation $sendToOwnersOfOrganisation;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->identityResolver = $this->createMock(UserIdentityResolver::class);

        $this->sendToOwnersOfOrganisation = new SendToOwnersOfOrganisation(
            $this->identityResolver,
            new DBALOwnershipSearchRepository($this->getConnection())
        );
    }

    /** @test */
    public function it_should_return_the_correct_and_valid_users(): void
    {
        $itemId = '42e1829f-19d9-4b03-a5f6-b1938283df98 ';
        $ownerId = '4f850240-4f7f-4a7f-9828-f574da6f97d0';

        $this->connection->insert('ownership_search', [
            'id' => '94f8f4a1-440e-4ed3-9f52-c27be945f27f',
            'item_id' => $itemId,
            'item_type' => 'organizer',
            'owner_id' => $ownerId,
            'state' => OwnershipState::approved()->toString(),
            'role_id' => Uuid::uuid4()->toString(),
        ]);

        $this->connection->insert('ownership_search', [
            'id' => Uuid::uuid4()->toString(),
            'item_id' => Uuid::uuid4()->toString(),//wrong item id
            'item_type' => 'organizer',
            'owner_id' => Uuid::uuid4()->toString(),
            'state' => OwnershipState::approved()->toString(),
            'role_id' => Uuid::uuid4()->toString(),
        ]);

        $this->connection->insert('ownership_search', [
            'id' => Uuid::uuid4()->toString(),
            'item_id' => $itemId,
            'item_type' => 'organizer',
            'owner_id' => $ownerId,
            'state' => OwnershipState::rejected()->toString(),//wrong state
            'role_id' => Uuid::uuid4()->toString(),
        ]);

        $ownershipItem = new OwnershipItem(
            Uuid::uuid4()->toString(),
            $itemId,
            'organizer',
            Uuid::uuid4()->toString(),
            OwnershipState::approved()->toString(),
        );

        $this->identityResolver->expects($this->once())
            ->method('getUserById')
            ->with($ownerId)
            ->willReturn(new UserIdentityDetails(
                $ownerId,
                'Grote smurf',
                'grotesmurf@publiq.be'
            ));

        $recipients = $this->sendToOwnersOfOrganisation->getRecipients($ownershipItem);

        $this->assertCount(1, $recipients);
        $this->assertEquals($ownerId, $recipients[0]->getUserId());
        $this->assertEquals('grotesmurf@publiq.be', $recipients[0]->getEmailAddress());
    }
}
