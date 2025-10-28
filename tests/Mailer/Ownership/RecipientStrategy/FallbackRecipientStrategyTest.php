<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FallbackRecipientStrategyTest extends TestCase
{
    /**
     * @var RecipientStrategy&MockObject
     */
    private $decorateeRecipientStrategy;

    private UserIdentityDetails $fallbackIdentityDetails;

    private FallbackRecipientStrategy $fallbackRecipientStrategy;

    private OwnershipItem $ownershipItem;

    protected function setUp(): void
    {
        $this->decorateeRecipientStrategy = $this->createMock(RecipientStrategy::class);
        $this->fallbackIdentityDetails = new UserIdentityDetails(
            '5305a98a-bb55-4cbd-a376-658f8069aea8',
            'Helpdesk',
            'helpdesk@example.com'
        );

        $this->ownershipItem = new OwnershipItem(
            'c8e7671b-b8aa-4d44-8822-1a049f73afd0',
            'd4174e9b-9519-4bcf-bb77-9502beb5864a',
            ItemType::organizer()->toString(),
            'a90ff2fe-c518-4bd7-8e90-145653903745',
            OwnershipState::requested()->toString()
        );

        $this->fallbackRecipientStrategy = new FallbackRecipientStrategy(
            $this->fallbackIdentityDetails,
            $this->decorateeRecipientStrategy
        );
    }

    /**
     * @test
     */
    public function it_will_use_the_fallback_address_if_no_recipients_were_found(): void
    {
        $this->decorateeRecipientStrategy->expects($this->once())
            ->method('getRecipients')
            ->willReturn(new Recipients());

        $this->assertEquals(
            new Recipients($this->fallbackIdentityDetails),
            $this->fallbackRecipientStrategy->getRecipients($this->ownershipItem)
        );
    }

    /**
     * @test
     */
    public function it_will_not_use_the_fallback_address_if_recipients_were_found(): void
    {
        $userIdentityDetails = new UserIdentityDetails(
            'f39b8d60-316e-47fa-a2f9-acb1d22c4e8f',
            'Jane',
            'jane.doe@example.com'
        );

        $this->decorateeRecipientStrategy->expects($this->once())
            ->method('getRecipients')
            ->willReturn(new Recipients(
                $userIdentityDetails
            ));

        $this->assertEquals(
            new Recipients($userIdentityDetails),
            $this->fallbackRecipientStrategy->getRecipients($this->ownershipItem)
        );
    }
}
