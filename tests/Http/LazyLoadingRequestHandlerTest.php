<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Pimple;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use stdClass;

final class LazyLoadingRequestHandlerTest extends TestCase
{
    private Pimple $pimple;
    private RequestHandlerInterface $requestHandler;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->pimple = new Pimple();

        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->pimple['request_handler'] = $this->requestHandler;

        $this->pimple['random_service'] = new stdClass();
        $this->pimple['random_string'] = 'foo';

        $this->request = (new Psr7RequestBuilder())
            ->withUriFromString('/foo')
            ->build('GET');
    }

    /**
     * @test
     */
    public function it_gets_the_request_handler_from_the_container_and_delegates_the_request(): void
    {
        $response = new JsonResponse(['ok' => true]);

        $this->requestHandler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($response);

        $lazyLoadingRequestHandler = new LazyLoadingRequestHandler($this->pimple, 'request_handler');

        $this->assertEquals($response, $lazyLoadingRequestHandler->handle($this->request));
    }

    /**
     * @test
     */
    public function it_throws_a_runtime_exception_if_the_service_does_not_exist(): void
    {
        $this->requestHandler->expects($this->never())
            ->method('handle');

        $this->expectException(RuntimeException::class);

        $lazyLoadingRequestHandler = new LazyLoadingRequestHandler($this->pimple, 'service_does_not_exist');
        $lazyLoadingRequestHandler->handle($this->request);
    }

    /**
     * @test
     */
    public function it_throws_a_runtime_exception_if_the_service_is_not_an_object(): void
    {
        $this->requestHandler->expects($this->never())
            ->method('handle');

        $this->expectException(RuntimeException::class);

        $lazyLoadingRequestHandler = new LazyLoadingRequestHandler($this->pimple, 'random_string');
        $lazyLoadingRequestHandler->handle($this->request);
    }

    /**
     * @test
     */
    public function it_throws_a_runtime_exception_if_the_service_does_not_implement_RequestHandlerInterface(): void
    {
        $this->requestHandler->expects($this->never())
            ->method('handle');

        $this->expectException(RuntimeException::class);

        $lazyLoadingRequestHandler = new LazyLoadingRequestHandler($this->pimple, 'random_service');
        $lazyLoadingRequestHandler->handle($this->request);
    }
}
