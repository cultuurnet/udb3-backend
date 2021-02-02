<?php

namespace CultuurNet\UDB3\Role\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class AbstractUserEventTest extends TestCase
{
    /**
     * @var AbstractUserEvent
     */
    private $abstractUserEvent;

    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var StringLiteral
     */
    private $userId;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->userId = new StringLiteral('userId');

        $this->abstractUserEvent = $this->getMockForAbstractClass(
            AbstractUserEvent::class,
            [$this->uuid, $this->userId]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->abstractUserEvent->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_user_id()
    {
        $this->assertEquals($this->userId, $this->abstractUserEvent->getUserId());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualArray = $this->abstractUserEvent->serialize();

        $expectedArray = [
            AbstractUserEvent::UUID => $this->uuid->toNative(),
            AbstractUserEvent::USER_ID => $this->userId->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }
}
