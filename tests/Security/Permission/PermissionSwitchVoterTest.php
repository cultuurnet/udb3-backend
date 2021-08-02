<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class PermissionSwitchVoterTest extends TestCase
{
    /**
     * @test
     */
    public function it_delegates_to_a_voter_responsible_for_the_permission()
    {
        $offerId = new StringLiteral('232');
        $userId = new StringLiteral('john_doe');
        $organizerId = new StringLiteral('organizer_abc');

        $a = $this->getMockBuilder(PermissionVoter::class)->getMock();
        $b = $this->getMockBuilder(PermissionVoter::class)->getMock();

        $voter = (new PermissionSwitchVoter())
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

    /**
     * @test
     */
    public function it_can_use_a_default_voter_for_permissions_that_do_not_have_a_specific_voter(): void
    {
        $offerId = new StringLiteral('232');
        $userId = new StringLiteral('john_doe');

        $specific = $this->getMockBuilder(PermissionVoter::class)->getMock();
        $default = $this->getMockBuilder(PermissionVoter::class)->getMock();

        $voter = (new PermissionSwitchVoter())
            ->withVoter($specific, Permission::AANBOD_VERWIJDEREN())
            ->withDefaultVoter($default);

        $specific->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::AANBOD_VERWIJDEREN(), $offerId, $userId)
            ->willReturn(false);

        $default->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::LABELS_BEHEREN(), $offerId, $userId)
            ->willReturn(true);

        $this->assertFalse(
            $voter->isAllowed(Permission::AANBOD_VERWIJDEREN(), $offerId, $userId)
        );

        $this->assertTrue(
            $voter->isAllowed(Permission::LABELS_BEHEREN(), $offerId, $userId)
        );
    }
}
