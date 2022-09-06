<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class InvokableRequestHandlerTest extends TestCase
{
    private MockObject $requestHandler;
    private InvokableRequestHandler $invokableRequestHandler;

    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->invokableRequestHandler = new InvokableRequestHandler($this->requestHandler);
    }

    /**
     * @test
     */
    public function it_is_callable(): void
    {
        $this->assertIsCallable($this->invokableRequestHandler);
    }

    /**
     * @test
     */
    public function it_delegates_the_invoke_method_call_to_the_handle_method_of_the_decorated_request_handler(): void
    {
        $request = (new Psr7RequestBuilder())->withUriFromString('/mock')->build('GET');

        $this->requestHandler->expects($this->once())
            ->method('handle')
            ->with($request);

        call_user_func($this->invokableRequestHandler, $request);
    }

    /**
     * @test
     */
    public function it_implements_the_psr_request_handler_interface(): void
    {
        $request = (new Psr7RequestBuilder())->withUriFromString('/mock')->build('GET');

        $this->requestHandler->expects($this->once())
            ->method('handle')
            ->with($request);

        $this->invokableRequestHandler->handle($request);
    }
}
