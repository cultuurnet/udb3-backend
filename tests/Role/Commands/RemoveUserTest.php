<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class RemoveUserTest extends TestCase
{
    private RemoveUser $removeUser;

    protected function setUp(): void
    {
        $uuid = new UUID('67780d64-b401-4040-af1f-5f424e0b7306');

        $userId = 'userId';

        $this->removeUser = new RemoveUser($uuid, $userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->removeUser,
            AbstractCommand::class
        ));
    }
}
