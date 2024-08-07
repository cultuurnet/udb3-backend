<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Error\WebErrorHandler;
use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route;
use League\Route\Strategy\AbstractStrategy;
use League\Route\Strategy\OptionsHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Copied from League\Route\Strategy\ApplicationStrategy as a base to customize, as suggested by the League Router docs.
 * Note that we did not use League\Route\Strategy\JsonStrategy because it was a bit too opinionated about for example
 * error responses.
 *
 * Expanded to implement OptionsHandlerInterface, to support OPTIONS requests for every registered route without having
 * to register a catch-all route which resulted in "Method Not Allowed" errors instead of "Not Found" errors if an
 * unknown URL was requested.
 */
final class CustomLeagueRouterStrategy extends AbstractStrategy implements
    ContainerAwareInterface,
    OptionsHandlerInterface
{
    use ContainerAwareTrait;

    private WebErrorHandler $webErrorHandler;

    public function __construct(WebErrorHandler $webErrorHandler)
    {
        $this->webErrorHandler = $webErrorHandler;
    }

    public function getOptionsCallable(array $methods): callable
    {
        return static function (): ResponseInterface {
            // Just return a 204 No Content for every OPTIONS request.
            // Actual CORS headers are added via a middleware, so they are also added to other requests.
            return new NoContentResponse();
        };
    }

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return $this->webErrorHandler;
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());
        $response = $controller($request, $route->getVars());
        return $this->decorateResponse($response);
    }

    protected function throwThrowableMiddleware(Throwable $error): MiddlewareInterface
    {
        return new class ($error) implements MiddlewareInterface {
            protected Throwable $error;

            public function __construct(Throwable $error)
            {
                $this->error = $error;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                throw $this->error;
            }
        };
    }
}
