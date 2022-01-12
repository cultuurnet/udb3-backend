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
            ->withVoter($a, Permission::labelsBeheren(), Permission::aanbodVerwijderen())
            ->withVoter($b, Permission::organisatiesBewerken());

        $a->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodVerwijderen(), $offerId, $userId)
            ->willReturn(true);

        $b->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::organisatiesBewerken(), $organizerId, $userId)
            ->willReturn(false);

        $this->assertTrue(
            $voter->isAllowed(Permission::aanbodVerwijderen(), $offerId, $userId)
        );
        $this->assertFalse(
            $voter->isAllowed(Permission::organisatiesBewerken(), $organizerId, $userId)
        );

        $this->assertFalse(
            $voter->isAllowed(Permission::aanbodModereren(), $offerId, $userId)
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
            ->withVoter($specific, Permission::aanbodVerwijderen())
            ->withDefaultVoter($default);

        $specific->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodVerwijderen(), $offerId, $userId)
            ->willReturn(false);

        $default->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::labelsBeheren(), $offerId, $userId)
            ->willReturn(true);

        $this->assertFalse(
            $voter->isAllowed(Permission::aanbodVerwijderen(), $offerId, $userId)
        );

        $this->assertTrue(
            $voter->isAllowed(Permission::labelsBeheren(), $offerId, $userId)
        );
    }
}
