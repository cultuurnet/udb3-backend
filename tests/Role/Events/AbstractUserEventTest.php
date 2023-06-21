<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class AbstractUserEventTest extends TestCase
{
    /**
     * @var AbstractUserEvent|MockObject
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

    protected function setUp(): void
    {
        $this->uuid = new UUID('7c296342-d72b-4444-9f8b-2a0c99763c9a');

        $this->userId = new StringLiteral('userId');

        $this->abstractUserEvent = $this->getMockForAbstractClass(
            AbstractUserEvent::class,
            [$this->uuid, $this->userId]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->abstractUserEvent->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_user_id(): void
    {
        $this->assertEquals($this->userId, $this->abstractUserEvent->getUserId());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualArray = $this->abstractUserEvent->serialize();

        $expectedArray = [
            AbstractUserEvent::UUID => $this->uuid->toString(),
            AbstractUserEvent::USER_ID => $this->userId->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }
}
