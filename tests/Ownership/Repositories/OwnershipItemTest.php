<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories;

use CultuurNet\UDB3\Ownership\OwnershipState;
use PHPUnit\Framework\TestCase;

class OwnershipItemTest extends TestCase
{
    private OwnershipItem $ownershipItem;

    protected function setUp(): void
    {
        $this->ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
    }

    /**
     * @test
     */
    public function it_has_an_id(): void
    {
        $this->assertEquals('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e', $this->ownershipItem->getId());
    }

    /**
     * @test
     */
    public function it_has_an_item_id(): void
    {
        $this->assertEquals('9e68dafc-01d8-4c1c-9612-599c918b981d', $this->ownershipItem->getItemId());
    }

    /**
     * @test
     */
    public function it_has_an_item_type(): void
    {
        $this->assertEquals('organizer', $this->ownershipItem->getItemType());
    }

    /**
     * @test
     */
    public function it_has_an_owner_id(): void
    {
        $this->assertEquals('auth0|63e22626e39a8ca1264bd29b', $this->ownershipItem->getOwnerId());
    }

    /**
     * @test
     */
    public function it_has_an_ownership_state(): void
    {
        $this->assertEquals(
            OwnershipState::requested()->toString(),
            $this->ownershipItem->getState()
        );
    }

    public function it_can_set_approved_by(): void
    {
        $this->assertEquals(
            'auth0|63e22626e39a8ca1264bd29b',
            $this->ownershipItem->withApprovedBy('auth0|63e22626e39a8ca1264bd29b')
        );
    }

    public function it_can_set_denied_by(): void
    {
        $this->assertEquals(
            'auth0|63e22626e39a8ca1264bd29b',
            $this->ownershipItem->withRejectedBy('auth0|63e22626e39a8ca1264bd29b')
        );
    }

    public function it_can_set_deleted_by(): void
    {
        $this->assertEquals(
            'auth0|63e22626e39a8ca1264bd29b',
            $this->ownershipItem->withDeletedBy('auth0|63e22626e39a8ca1264bd29b')
        );
    }
}
