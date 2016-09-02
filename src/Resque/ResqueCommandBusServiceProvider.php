<?php

namespace CultuurNet\UDB3\Silex\Resque;

use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus;
use CultuurNet\UDB3\Silex\ContextDecoratedCommandBus;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ResqueCommandBusServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['resque_command_bus_factory'] = $app->protect(
            function ($queueName) use ($app) {
                $app[$queueName . '_command_bus_factory'] = function () use ($app, $queueName) {
                    $commandBus = new ResqueCommandBus(
                        new SimpleContextAwareCommandBus(),
                        $queueName,
                        $app['command_bus_event_dispatcher']
                    );

                    $commandBus->setLogger($app['logger.command_bus']);

                    return $commandBus;
                };

                $app[$queueName . '_command_bus'] = $app->share(
                    function (Application $app) use ($queueName) {
                        return new ContextDecoratedCommandBus(
                            $app[$queueName . '_command_bus_base'],
                            $app
                        );
                    }
                );

                $app[$queueName . '_command_bus_out'] = $app->share(
                    function (Application $app) use ($queueName) {
                        return $app[$queueName . '_command_bus_base'];
                    }
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
