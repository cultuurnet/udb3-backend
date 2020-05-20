<?php

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class PermissionSplitVoterTest extends TestCase
{
    /**
     * @test
     */
    public function it_delegates_to_a_voter_responsible_for_the_permission()
    {
        $offerId = new StringLiteral('232');
        $userId = new StringLiteral('john_doe');
        $organizerId = new StringLiteral('organizer_abc');

        $a = $this->getMockBuilder(PermissionVoterInterface::class)->getMock();
        $b = $this->getMockBuilder(PermissionVoterInterface::class)->getMock();

        $voter = (new PermissionSplitVoter())
            ->withVoter($a, Permission::LABELS_BEHEREN(), Permission::AANBOD_VERWIJDEREN())
            ->withVoter($b, Permission::ORGANISATIES_BEWERKEN());

        $a->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::AANBOD_VERWIJDEREN(), $offerId, $userId)
            ->willReturn(true);

        $b->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::ORGANISATIES_BEWERKEN(), $organizerId, $userId)
            ->willReturn(false);

        $this->assertTrue(
            $voter->isAllowed(Permission::AANBOD_VERWIJDEREN(), $offerId, $userId)
        );
        $this->assertFalse(
            $voter->isAllowed(Permission::ORGANISATIES_BEWERKEN(), $organizerId, $userId)
        );

        $this->assertFalse(
            $voter->isAllowed(Permission::AANBOD_MODEREREN(), $offerId, $userId)
        );
    }
}
