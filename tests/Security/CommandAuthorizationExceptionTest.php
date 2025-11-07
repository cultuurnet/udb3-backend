<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandAuthorizationExceptionTest extends TestCase
{
    private string $userId;

    private Permission $permission;

    private string $itemId;

    private AuthorizableCommand&MockObject $command;

    private CommandAuthorizationException $commandAuthorizationException;

    protected function setUp(): void
    {
        $this->userId = '85b040e5-766a-4ca7-a01b-e21e9250165f';
        $this->permission = Permission::aanbodBewerken();
        $this->itemId = '69aa5d8d-5d56-4774-9320-d8e7c1721693';

        $this->command = $this->createMock(AuthorizableCommand::class);
        $this->command->method('getPermission')
            ->willReturn($this->permission);
        $this->command->method('getItemId')
            ->willReturn($this->itemId);

        $this->commandAuthorizationException = new CommandAuthorizationException(
            $this->userId,
            $this->command
        );
    }

    /**
     * @test
     */
    public function it_stores_a_user_id(): void
    {
        $this->assertEquals(
            $this->userId,
            $this->commandAuthorizationException->getUserId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_command(): void
    {
        $this->assertEquals(
            $this->command,
            $this->commandAuthorizationException->getCommand()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_message(): void
    {
        $expectedMessage = 'User with id: ' . $this->userId .
            ' has no permission: "' . $this->permission->toString() .
            '" on item: ' . $this->itemId .
            ' when executing command: ' . get_class($this->command);

        $this->assertEquals(
            $expectedMessage,
            $this->commandAuthorizationException->getMessage()
        );
    }
}
