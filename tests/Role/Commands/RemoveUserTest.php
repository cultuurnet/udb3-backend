<?php

namespace CultuurNet\UDB3\Role\Commands;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class RemoveUserTest extends TestCase
{
    /**
     * @var RemoveUser
     */
    private $removeUser;

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

        $this->removeUser = new RemoveUser($this->uuid, $this->userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->removeUser,
            AbstractCommand::class
        ));
    }
}
