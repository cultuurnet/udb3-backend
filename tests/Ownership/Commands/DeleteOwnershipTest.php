<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class DeleteOwnershipTest extends TestCase
{
    private DeleteOwnership $deleteOwnership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteOwnership = new DeleteOwnership(
            new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(
            new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            $this->deleteOwnership->getId()
        );
    }
}
