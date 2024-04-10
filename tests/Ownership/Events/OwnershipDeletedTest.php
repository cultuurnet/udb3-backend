<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use PHPUnit\Framework\TestCase;

class OwnershipDeletedTest extends TestCase
{
    private OwnershipDeleted $ownershipDeleted;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownershipDeleted = new OwnershipDeleted(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $this->ownershipDeleted->getId()
        );
    }
}
