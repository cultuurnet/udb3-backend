<?php

namespace CultuurNet\UDB3\Silex\Import;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ImportServiceProvider implements ServiceProviderInterface
{
    /**
     * @var callable
     */
    private $subscribeHandlersCallback;

    /**
     * @param callable $subscribeHandlersCallback
     */
    public function __construct(callable $subscribeHandlersCallback)
    {
        $this->subscribeHandlersCallback = $subscribeHandlersCallback;
    }

    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        // Set up the import resque command bus.
        $app['resque_command_bus_factory']('imports');

        // Tie the relevant command handlers to the command bus.
        $app->extend('imports_command_bus_out', $this->subscribeHandlersCallback);
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
