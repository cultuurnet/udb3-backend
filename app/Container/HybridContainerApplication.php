<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Container;

use League\Container\DefinitionContainerInterface;
use Silex\Application;

/**
 * Same as the standard Silex application, but when fetching a service (like $app[ServiceName::class]) and its not
 * defined in the Silex/Pimple container, it gets the service from a PSR-11 container that is injected. This way we can
 * move services one by one to a PSR-11 container without breaking existing service definitions in the Silex
 * application.
 */
final class HybridContainerApplication extends Application
{
    private DefinitionContainerInterface $leagueContainer;

    public function __construct(DefinitionContainerInterface $leagueContainer)
    {
        parent::__construct();
        $this->leagueContainer = $leagueContainer;
    }

    public function offsetGet($id)
    {
        if ($this->offsetExists($id)) {
            return parent::offsetGet($id);
        }
        return $this->leagueContainer->get($id);
    }

    public function getLeagueContainer(): DefinitionContainerInterface
    {
        return $this->leagueContainer;
    }
}
