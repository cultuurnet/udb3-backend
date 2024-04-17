<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Decorates a PSR service container. When an entry that implements Psr\Http\Server\RequestHandlerInterface is requested
 * via get(), it will be decorated with InvokableRequestHandler.
 * The primary use case for this is to use this container for lazy-loading route handlers in the League router, while at
 * the same time making the request handlers automatically implement __invoke() because the League router does not have
 * support for the PSR RequestHandlerInterface out of the box at the time of writing.
 */
final class InvokableRequestHandlerContainer implements ContainerInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get($id)
    {
        $service = $this->container->get($id);
        if ($service instanceof RequestHandlerInterface && !is_callable($service)) {
            return new InvokableRequestHandler($service);
        }
        return $service;
    }

    public function has($id): bool
    {
        return $this->container->has($id);
    }
}
