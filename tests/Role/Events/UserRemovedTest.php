<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class UserRemovedTest extends TestCase
{
    /**
     * @var UserRemoved
     */
    private $userRemoved;

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
        $this->uuid = new UUID('510610a1-ffe0-4e10-a396-7d0cb28e0619');

        $this->userId = new StringLiteral('userId');

        $this->userRemoved = new UserRemoved($this->uuid, $this->userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_event()
    {
        $this->assertTrue(is_subclass_of(
            $this->userRemoved,
            AbstractUserEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $userRemovedAsArray = [
            AbstractUserEvent::UUID => $this->uuid->toString(),
            AbstractUserEvent::USER_ID => $this->userId->toNative(),
        ];

        $actualUserAdded = UserRemoved::deserialize($userRemovedAsArray);

        $this->assertEquals($this->userRemoved, $actualUserAdded);
    }
}
