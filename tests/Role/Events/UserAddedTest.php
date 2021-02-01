<?php

namespace CultuurNet\UDB3\Role\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UserAddedTest extends TestCase
{
    /**
     * @var UserAdded
     */
    private $userAdded;

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

        $this->userAdded = new UserAdded($this->uuid, $this->userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_event()
    {
        $this->assertTrue(is_subclass_of(
            $this->userAdded,
            AbstractUserEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $userAddedAsArray = [
            AbstractUserEvent::UUID => $this->uuid->toNative(),
            AbstractUserEvent::USER_ID => $this->userId->toNative(),
        ];

        $actualUserAdded = UserAdded::deserialize($userAddedAsArray);

        $this->assertEquals($this->userAdded, $actualUserAdded);
    }
}
