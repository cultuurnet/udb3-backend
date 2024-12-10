<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class AddUserTest extends TestCase
{
    private AddUser $addUser;

    protected function setUp(): void
    {
        $uuid = new Uuid('7ce4cb7e-af7c-4724-bfbe-d943ac2b949a');

        $userId = 'userId';

        $this->addUser = new AddUser($uuid, $userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->addUser,
            AbstractCommand::class
        ));
    }
}
