<?php

namespace CultuurNet\UDB3\Silex\Offer;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus;
use CultuurNet\UDB3\Offer\BulkLabelCommandHandler;
use CultuurNet\UDB3\Silex\ContextDecoratedCommandBus;
use Silex\Application;
use Silex\ServiceProviderInterface;

class BulkLabelOfferServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['bulk_label_offer_command_bus_factory'] = $app->share(
            function (Application $app) {
                return function () use ($app) {
                    $commandBus = new ResqueCommandBus(
                        new SimpleContextAwareCommandBus(),
                        'bulk_label_offer_commands',
                        $app['command_bus_event_dispatcher']
                    );

                    $commandBus->setLogger($app['logger.command_bus']);

                    return $commandBus;
                };
            }
        );

        $app['bulk_label_offer_command_bus_in'] = $app->share(
            function (Application $app) {
                return new ContextDecoratedCommandBus(
                    $app['bulk_label_offer_command_bus_factory'](),
                    $app
                );
            }
        );

        $app['bulk_label_offer_command_bus_out'] = $app->share(
            function (Application $app) {
                return $app['bulk_label_offer_command_bus_factory']();
            }
        );

        $app['bulk_label_offer_command_handler'] = $app->share(
            function (Application $app) {
                return new BulkLabelCommandHandler(
                    $app['search_results_generator'],
                    $app['external_offer_editing_service']
                );
            }
        );

        $app->extend(
            'bulk_label_offer_command_bus_out',
            function (CommandBusInterface $commandBus, Application $app) {
                $commandBus->subscribe($app['bulk_label_offer_command_handler']);
                return $commandBus;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
