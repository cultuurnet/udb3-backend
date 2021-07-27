<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityDecoratorBaseTest extends TestCase
{
    /**
     * @var SecurityInterface|MockObject
     */
    private $decoratee;

    /**
     * @var SecurityDecoratorBase|MockObject
     */
    private $decoratorBase;

    protected function setUp()
    {
        $this->decoratee = $this->createMock(SecurityInterface::class);

        $this->decoratee->method('isAuthorized')
            ->willReturn(true);

        $this->decoratorBase = $this->getMockForAbstractClass(
            SecurityDecoratorBase::class,
            [$this->decoratee]
        );
    }

    /**
     * @test
     */
    public function it_calls_is_authorized_from_decoratee()
    {
        $command = $this->createMock(AuthorizableCommandInterface::class);

        $this->decoratee->expects($this->once())
            ->method('isAuthorized')
            ->with($command);

        $this->decoratorBase->isAuthorized($command);
    }

    /**
     * @test
     */
    public function it_returns_is_authorized_result_from_decoratee()
    {
        $command = $this->createMock(AuthorizableCommandInterface::class);

        $this->assertTrue($this->decoratorBase->isAuthorized($command));
    }
}
