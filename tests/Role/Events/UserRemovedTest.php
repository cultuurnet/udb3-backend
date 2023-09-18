<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class UserRemovedTest extends TestCase
{
    private UserRemoved $userRemoved;

    private UUID $uuid;

    private string $userId;

    protected function setUp(): void
    {
        $this->uuid = new UUID('510610a1-ffe0-4e10-a396-7d0cb28e0619');

        $this->userId = 'userId';

        $this->userRemoved = new UserRemoved($this->uuid, $this->userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_event(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->userRemoved,
            AbstractUserEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $userRemovedAsArray = [
            AbstractUserEvent::UUID => $this->uuid->toString(),
            AbstractUserEvent::USER_ID => $this->userId,
        ];

        $actualUserAdded = UserRemoved::deserialize($userRemovedAsArray);

        $this->assertEquals($this->userRemoved, $actualUserAdded);
    }
}
