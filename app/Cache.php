<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Silex\Application;

/**
 * Wrapper service to provide access to cache without
 * having to inject the entire container. The idea is
 * to make it easier to move away from using Silex
 * framework.
 * @TODO: Refactor when moving from Silex
 * this is a temporary solution
 */
class Cache
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function useCache(string $name)
    {
        $cacheServiceName = 'cache-' . $name;

        $this->application['cache'] = $this->application->share(
            function () use ($cacheServiceName) {
                return $this->application[$cacheServiceName];
            }
        );
    }
}
