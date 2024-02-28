<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class RequestOwnershipTest extends TestCase
{
    private RequestOwnership $requestOwnership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestOwnership = new RequestOwnership(
            new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            new UUID('9e68dafc-01d8-4c1c-9612-599c918b981d'),
            ItemType::organizer(),
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
            $this->requestOwnership->getId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_id(): void
    {
        $this->assertEquals(
            new UUID('9e68dafc-01d8-4c1c-9612-599c918b981d'),
            $this->requestOwnership->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_type(): void
    {
        $this->assertEquals(
            ItemType::organizer(),
            $this->requestOwnership->getItemType()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_owner_id(): void
    {
        $this->assertEquals(
            new UserId('auth0|63e22626e39a8ca1264bd29b'),
            $this->requestOwnership->getOwnerId()
        );
    }
}
