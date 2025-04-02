<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use PHPUnit\Framework\TestCase;

class OwnershipApprovedTest extends TestCase
{
    private const USER_ID = 'auth0|63e22626e39a8ca1264bd29b';

    private OwnershipApproved $ownershipApproved;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownershipApproved = new OwnershipApproved('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e', self::USER_ID);
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $this->ownershipApproved->getId()
        );
    }

    /** @test */
    public function it_stores_a_user_id(): void
    {
        $this->assertEquals(
            self::USER_ID,
            $this->ownershipApproved->getUserId()
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
            $this->ownershipApproved->serialize()
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

        $this->assertEquals($this->ownershipApproved, OwnershipApproved::deserialize($serialized));
    }
}
