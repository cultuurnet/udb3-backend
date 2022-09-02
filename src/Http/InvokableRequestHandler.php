<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Decorates a given RequestHandlerInterface to make it implement __invoke() for easier usage in the League router,
 * which does not support RequestHandlerInterface for route handlers out of the box at the time of writing.
 */
final class InvokableRequestHandler implements RequestHandlerInterface
{
    private RequestHandlerInterface $requestHandler;

    public function __construct(RequestHandlerInterface $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->requestHandler->handle($request);
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }
}
