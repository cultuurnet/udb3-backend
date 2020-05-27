<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
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
     * @var SecurityDecoratorBase
     */
    private $decoratorBase;

    protected function setUp()
    {
        $this->decoratee = $this->createMock(SecurityInterface::class);

        $this->decoratee->method('allowsUpdateWithCdbXml')
            ->willReturn(true);

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
    public function it_calls_allows_update_with_cdbxml_from_decoratee()
    {
        $offerId = new StringLiteral('a75bea80-bf8f-4c23-8368-e7153f9685b5');

        $this->decoratee->expects($this->once())
            ->method('allowsUpdateWithCdbXml')
            ->with($offerId);

        $this->decoratorBase->allowsUpdateWithCdbXml($offerId);
    }

    /**
     * @test
     */
    public function it_returns_allows_update_with_cdbxml_result_from_decoratee()
    {
        $offerId = new StringLiteral('b83642be-2b56-43d2-93f4-2f1dd9f7529a');

        $this->assertTrue($this->decoratorBase->allowsUpdateWithCdbXml($offerId));
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
