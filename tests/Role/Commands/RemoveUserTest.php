<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

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
        $this->uuid = new UUID('67780d64-b401-4040-af1f-5f424e0b7306');

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
