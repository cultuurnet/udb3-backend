<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class InvokableRequestHandlerContainerTest extends TestCase
{
    private array $services;
    private MockObject $requestHandler;
    private RequestHandlerInterface $alreadyCallableRequestHandler;
    private InvokableRequestHandlerContainer $invokableRequestHandlerContainer;

    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);

        $this->alreadyCallableRequestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new NoContentResponse();
            }

            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return $this->handle($request);
            }
        };

        $this->services = [
            'mock_service' => new stdClass(),
            'mock_request_handler' => $this->requestHandler,
            'mock_request_handler_callable' => $this->alreadyCallableRequestHandler,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function (string $id) {
                    return $this->services[$id] ?? null;
                }
            );
        $container->expects($this->any())
            ->method('has')
            ->willReturnCallback(
                function (string $id) {
                    return isset($this->services[$id]);
                }
            );

        $this->invokableRequestHandlerContainer = new InvokableRequestHandlerContainer($container);
    }

    /**
     * @test
     */
    public function it_decorates_request_handlers_to_make_them_invokable_if_they_are_not_yet(): void
    {
        $modifiedRequestHandler = $this->invokableRequestHandlerContainer->get('mock_request_handler');
        $alreadyCallableRequestHandler = $this->invokableRequestHandlerContainer->get('mock_request_handler_callable');

        $this->assertIsCallable($modifiedRequestHandler);
        $this->assertIsCallable($alreadyCallableRequestHandler);

        // Make sure the request handler that was already callable is not altered
        $this->assertEquals($this->alreadyCallableRequestHandler, $alreadyCallableRequestHandler);

        // Make sure the modified request handler still works
        $request = (new Psr7RequestBuilder())->withUriFromString('/mock')->build('GET');
        $this->requestHandler->expects($this->once())->method('handle')->with($request);
        $modifiedRequestHandler->handle($request);
    }

    /**
     * @test
     */
    public function it_returns_other_services_as_they_are(): void
    {
        $this->assertEquals(new stdClass(), $this->invokableRequestHandlerContainer->get('mock_service'));
    }

    /**
     * @test
     */
    public function it_delegates_the_has_method_to_the_decorated_container(): void
    {
        $this->assertTrue($this->invokableRequestHandlerContainer->has('mock_service'));
        $this->assertTrue($this->invokableRequestHandlerContainer->has('mock_request_handler'));
        $this->assertFalse($this->invokableRequestHandlerContainer->has('foo'));
    }
}
