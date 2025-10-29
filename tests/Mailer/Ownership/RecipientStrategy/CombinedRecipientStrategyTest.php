<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;
use PHPUnit\Framework\TestCase;

final class CombinedRecipientStrategyTest extends TestCase
{
    private OwnershipItem $ownershipItem;
    private UserIdentityDetails $recipient1;
    private UserIdentityDetails $recipient2;
    private UserIdentityDetails $recipient3;

    private UserIdentityDetails $fallbackIdentityDetails;

    protected function setUp(): void
    {
        $this->ownershipItem = new OwnershipItem(
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            'organizer',
            Uuid::uuid4()->toString(),
            'requested'
        );

        $this->recipient1 = new UserIdentityDetails(
            Uuid::uuid4()->toString(),
            'Grote smurf',
            'grotesmurf@publiq.be'
        );
        $this->recipient2 = new UserIdentityDetails(
            Uuid::uuid4()->toString(),
            'smurfin',
            'smurfin@publiq.be'
        );
        $this->recipient3 = new UserIdentityDetails(
            Uuid::uuid4()->toString(),
            'Brilsmurf',
            'brilsmurf@publiq.be'
        );

        $this->fallbackIdentityDetails = new UserIdentityDetails(
            Uuid::uuid4()->toString(),
            'Helpdesk',
            'helpdesk@example.com'
        );
    }

    /** @test */
    public function get_recipients_combines_results(): void
    {
        $strategyReturns2Items = $this->createMock(RecipientStrategy::class);
        $strategyReturns2Items->method('getRecipients')->with($this->ownershipItem)->willReturn(new Recipients($this->recipient1, $this->recipient2));

        $recipientStrategyReturn1Item = $this->createMock(RecipientStrategy::class);
        $recipientStrategyReturn1Item->method('getRecipients')->with($this->ownershipItem)->willReturn(new Recipients($this->recipient3));

        $combinedStrategy = new CombinedRecipientStrategy($strategyReturns2Items, $recipientStrategyReturn1Item);
        $recipients = $combinedStrategy->getRecipients($this->ownershipItem);

        $this->assertCount(3, $recipients);
        $this->assertEquals((new Recipients($this->recipient1, $this->recipient2, $this->recipient3))->getRecipients(), $recipients->getRecipients());
    }

    /** @test */
    public function make_sure_nobody_gets_email_double(): void
    {
        $strategyReturns2Items = $this->createMock(RecipientStrategy::class);
        $strategyReturns2Items->method('getRecipients')->with($this->ownershipItem)->willReturn(new Recipients($this->recipient1, $this->recipient2));

        $recipientStrategyReturn1Item = $this->createMock(RecipientStrategy::class);
        $recipientStrategyReturn1Item->method('getRecipients')->with($this->ownershipItem)->willReturn(new Recipients($this->recipient1));

        $combinedStrategy = new CombinedRecipientStrategy($strategyReturns2Items, $recipientStrategyReturn1Item);
        $recipients = $combinedStrategy->getRecipients($this->ownershipItem);

        $this->assertCount(2, $recipients);
        $this->assertEquals((new Recipients($this->recipient1, $this->recipient2))->getRecipients(), $recipients->getRecipients());
    }

    /**
     * @test
     */
    public function it_will_use_the_fallback_address_if_no_recipients_were_found(): void
    {
        $decorateeRecipientStrategy = $this->createMock(RecipientStrategy::class);

        $combinedStrategyWithFallback = (new CombinedRecipientStrategy($decorateeRecipientStrategy))
            ->withFallback($this->fallbackIdentityDetails);

        $decorateeRecipientStrategy->expects($this->once())
            ->method('getRecipients')
            ->willReturn(new Recipients());

        $this->assertEquals(
            new Recipients($this->fallbackIdentityDetails),
            $combinedStrategyWithFallback->getRecipients($this->ownershipItem)
        );
    }

    /**
     * @test
     */
    public function it_will_not_use_the_fallback_address_if_recipients_were_found(): void
    {
        $decorateeRecipientStrategy = $this->createMock(RecipientStrategy::class);

        $combinedStrategyWithFallback = (new CombinedRecipientStrategy($decorateeRecipientStrategy))
            ->withFallback($this->fallbackIdentityDetails);

        $decorateeRecipientStrategy->expects($this->once())
            ->method('getRecipients')
            ->willReturn(new Recipients($this->recipient1));

        $this->assertEquals(
            new Recipients($this->recipient1),
            $combinedStrategyWithFallback->getRecipients($this->ownershipItem)
        );
    }
}
