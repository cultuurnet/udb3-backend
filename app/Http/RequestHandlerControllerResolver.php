<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use Pimple;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
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

        // If the controller is a service name and the service is an implementation of RequestHandlerInterface, wrap
        // the service in a closure and return that as callable for the router.
        if (is_string($controller) &&
            isset($this->serviceContainer[$controller]) &&
            $this->serviceContainer[$controller] instanceof RequestHandlerInterface) {
            $controller = $this->serviceContainer[$controller];
            return static fn (ServerRequestInterface $request) => $controller->handle($request);
        }

        // Otherwise defer to the default controller resolver. (For example if it's a closure or a string handled by
        // Silex\ServiceControllerResolver)
        return $this->fallbackControllerResolver->getController($request);
    }

    public function getArguments(Request $request, $controller): array
    {
        return $this->fallbackControllerResolver->getArguments($request, $controller);
    }
}
