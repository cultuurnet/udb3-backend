<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Container;

use Psr\Container\ContainerInterface;
use Silex\Application;

/**
 * Same as the standard Silex application, but when fetching a service (like $app[ServiceName::class]), it first checks
 * if the service is defined in a PSR-11 container that is injected. This way we can move services one by one to a
 * PSR-11 container without breaking existing service definitions in the Silex application.
 */
final class HybridContainerApplication extends Application
{
    private ContainerInterface $psrContainer;

    public function __construct(ContainerInterface $psrContainer)
    {
        parent::__construct();
        $this->psrContainer = $psrContainer;
    }

    public function offsetGet($id)
    {
        if ($this->psrContainer->has($id)) {
            return $this->psrContainer->get($id);
        }
        return parent::offsetGet($id);
    }
}
