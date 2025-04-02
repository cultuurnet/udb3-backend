<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use PHPUnit\Framework\TestCase;

class OwnershipRejectedTest extends TestCase
{
    private const USER_ID = 'auth0|63e22626e39a8ca1264bd29b';

    private OwnershipRejected $ownershipRejected;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownershipRejected = new OwnershipRejected('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e', self::USER_ID);
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

    /** @test */
    public function it_stores_a_user_id(): void
    {
        $this->assertEquals(
            self::USER_ID,
            $this->ownershipRejected->getUserId()
        );
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $this->assertEquals(
            [
                'ownershipId' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                'userId' => self::USER_ID,
            ],
            $this->ownershipRejected->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $serialized = [
            'ownershipId' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            'userId' => self::USER_ID,
        ];

        $this->assertEquals($this->ownershipRejected, OwnershipRejected::deserialize($serialized));
    }
}
