<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use InvalidArgumentException;
use Pimple;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class LazyLoadingRequestHandler implements RequestHandlerInterface
{
    private Pimple $serviceContainer;
    private string $serviceName;

    public function __construct(Pimple $serviceContainer, string $serviceName)
    {
        $this->serviceContainer = $serviceContainer;
        $this->serviceName = $serviceName;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $handler = $this->serviceContainer[$this->serviceName];
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException('Could not find request handler with service name ' . $this->serviceName);
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
