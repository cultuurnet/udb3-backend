<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AbstractUserCommandTest extends TestCase
{
    /**
     * @var AbstractUserCommand|MockObject
     */
    private $abstractUserCommand;

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
        $this->uuid = new UUID('ebb777b2-6735-4636-8f60-f7bde4576036');

        $this->userId = new StringLiteral('userId');

        $this->abstractUserCommand = $this
            ->getMockForAbstractClass(AbstractUserCommand::class, [$this->uuid, $this->userId]);
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->abstractUserCommand->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_user_id()
    {
        $this->assertEquals(
            $this->userId,
            $this->abstractUserCommand->getUserId()
        );
    }
}
