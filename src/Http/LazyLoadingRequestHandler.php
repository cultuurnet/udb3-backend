<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class LazyLoadingRequestHandler implements RequestHandlerInterface
{
    private ContainerInterface $serviceContainer;
    private string $serviceName;

    public function __construct(ContainerInterface $serviceContainer, string $serviceName)
    {
        $this->serviceContainer = $serviceContainer;
        $this->serviceName = $serviceName;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $handler = $this->serviceContainer->get($this->serviceName);
        } catch (Exception $e) {
            if ($e instanceof NotFoundExceptionInterface) {
                throw new RuntimeException('Could not find request handler with service name ' . $this->serviceName);
            }
            throw $e;
        }

        if (!$handler instanceof RequestHandlerInterface) {
            throw new RuntimeException('Request handler with service name ' . $this->serviceName . ' does not implement Psr\Http\Server\RequestHandlerInterface');
        }

        return $handler->handle($request);
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }
}
