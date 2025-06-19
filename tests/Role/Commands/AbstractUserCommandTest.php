<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUserCommandTest extends TestCase
{
    /**
     * III-5812 Add native TypeHint once upgrading to PHP 8
     */
    private AbstractUserCommand&MockObject $abstractUserCommand;

    private Uuid $uuid;

    private string $userId;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('ebb777b2-6735-4636-8f60-f7bde4576036');

        $this->userId = 'ebb777b2-6735-4636-8f60-f7bde4576036';

        $this->abstractUserCommand = $this
            ->getMockForAbstractClass(AbstractUserCommand::class, [$this->uuid, $this->userId]);
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->abstractUserCommand->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_user_id(): void
    {
        $this->assertEquals(
            $this->userId,
            $this->abstractUserCommand->getUserId()
        );
    }
}
