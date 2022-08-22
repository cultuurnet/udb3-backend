<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Request handler that handles any incoming request by delegating it to a router that implements
 * RequestHandlerInterface.
 *
 * Adds some extra sugar like rewriting the paths of incoming requests if needed (to support legacy URLs), and
 * converting exceptions/errors into API problems.
 */
final class ApplicationRequestHandler implements RequestHandlerInterface
{
    private RequestHandlerInterface $router;

    public function __construct(RequestHandlerInterface $router)
    {
        $this->router = $router;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = (new LegacyPathRewriter())->rewriteRequest($request);
        return $this->router->handle($request);
    }
}
