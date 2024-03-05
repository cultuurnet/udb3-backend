<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use PHPUnit\Framework\TestCase;

class OwnershipRequestedTest extends TestCase
{
    private OwnershipRequested $ownershipRequested;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownershipRequested = new OwnershipRequested(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
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
            $this->ownershipRequested->getId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_id(): void
    {
        $this->assertEquals(
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            $this->ownershipRequested->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_type(): void
    {
        $this->assertEquals(
            'organizer',
            $this->ownershipRequested->getItemType()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_owner_id(): void
    {
        $this->assertEquals(
            'auth0|63e22626e39a8ca1264bd29b',
            $this->ownershipRequested->getOwnerId()
        );
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $expected = [
            'ownershipId' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'itemType' => 'organizer',
            'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
        ];

        $this->assertEquals($expected, $this->ownershipRequested->serialize());
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $data = [
            'ownershipId' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'itemType' => 'organizer',
            'ownerId' => 'auth0|63e22626e39a8ca1264bd29b',
        ];

        $this->assertEquals($this->ownershipRequested, OwnershipRequested::deserialize($data));
    }
}
