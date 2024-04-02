<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use PHPUnit\Framework\TestCase;

class OwnershipRejectedTest extends TestCase
{
    private OwnershipRejected $ownershipRejected;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownershipRejected = new OwnershipRejected(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            'auth0|63e22626e39a8ca1264bd29b'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $this->ownershipRejected->getId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_requester_id(): void
    {
        $this->assertEquals(
            'auth0|63e22626e39a8ca1264bd29b',
            $this->ownershipRejected->getRequesterId()
        );
    }
}
