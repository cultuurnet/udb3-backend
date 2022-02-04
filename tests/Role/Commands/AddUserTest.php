<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AddUserTest extends TestCase
{
    /**
     * @var AddUser
     */
    private $addUser;

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
        $this->uuid = new UUID('7ce4cb7e-af7c-4724-bfbe-d943ac2b949a');

        $this->userId = new StringLiteral('userId');

        $this->addUser = new AddUser($this->uuid, $this->userId);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_user_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->addUser,
            AbstractCommand::class
        ));
    }
}
