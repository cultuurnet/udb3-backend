<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class ApproveOwnershipTest extends TestCase
{
    private ApproveOwnership $approveOwnership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approveOwnership = new ApproveOwnership(
            new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            new UserId('auth0|63e22626e39a8ca1264bd29b')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(
            new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            $this->approveOwnership->getId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_requester_id(): void
    {
        $this->assertEquals(
            new UserId('auth0|63e22626e39a8ca1264bd29b'),
            $this->approveOwnership->getRequesterId()
        );
    }
}
