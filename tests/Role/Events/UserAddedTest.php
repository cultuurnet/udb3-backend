<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class UserAddedTest extends TestCase
{
    private UserAdded $userAdded;

    private UUID $uuid;

    private string $userId;

    protected function setUp(): void
    {
        $this->uuid = new UUID('510610a1-ffe0-4e10-a396-7d0cb28e0619');

        $this->userId = 'userId';

        $this->userAdded = new UserAdded($this->uuid, $this->userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_event(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->userAdded,
            AbstractUserEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $userAddedAsArray = [
            AbstractUserEvent::UUID => $this->uuid->toString(),
            AbstractUserEvent::USER_ID => $this->userId,
        ];

        $actualUserAdded = UserAdded::deserialize($userAddedAsArray);

        $this->assertEquals($this->userAdded, $actualUserAdded);
    }
}
