<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use Pimple;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

final class RequestHandlerControllerResolver implements ControllerResolverInterface
{
    private ControllerResolverInterface $fallbackControllerResolver;
    private Pimple $serviceContainer;

    public function __construct(ControllerResolverInterface $fallbackControllerResolver, Pimple $serviceContainer)
    {
        $this->fallbackControllerResolver = $fallbackControllerResolver;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @return callable|false
     */
    public function getController(Request $request)
    {
        $controller = $request->attributes->get('_controller', null);
        if (!isset($this->serviceContainer[$controller]) ||
            !($this->serviceContainer[$controller] instanceof RequestHandlerInterface)) {
            return $this->fallbackControllerResolver->getController($request);
        }
        $controller = $this->serviceContainer[$controller];
        return static fn (ServerRequestInterface $request) => $controller->handle($request);
    }

    public function getArguments(Request $request, $controller): array
    {
        return $this->fallbackControllerResolver->getArguments($request, $controller);
    }
}
