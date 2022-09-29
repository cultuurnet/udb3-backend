<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Offer\AddLabelToMultipleRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelToQueryRequestHandler;
use CultuurNet\UDB3\Offer\BulkLabelCommandHandler;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultipleJSONDeserializer;
use CultuurNet\UDB3\Offer\IriOfferIdentifierJSONDeserializer;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class BulkLabelOfferServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        // Set up the bulk label offer command bus.
        $app['resque_command_bus_factory']('bulk_label_offer');

        // Set up the bulk label offer command handler.
        $app['bulk_label_offer_command_handler'] = $app->share(
            function (HybridContainerApplication $app) {
                $searchResultsGenerator = new ResultsGenerator(
                    $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_OFFERS]
                );
                $searchResultsGenerator->setLogger(
                    LoggerFactory::create($app->getLeagueContainer(), LoggerName::forResqueWorker('bulk-label-offer', 'search'))
                );

                return new BulkLabelCommandHandler(
                    $searchResultsGenerator,
                    $app['event_command_bus']
                );
            }
        );

        // Tie the bulk label offer command handler to the command bus.
        $app->extend(
            'bulk_label_offer_command_bus_out',
            function (CommandBus $commandBus, Application $app) {
                $commandBus->subscribe($app['bulk_label_offer_command_handler']);
                return $commandBus;
            }
        );

        $app[AddLabelToQueryRequestHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelToQueryRequestHandler(
                    $app['bulk_label_offer_command_bus']
                );
            }
        );

        $app[AddLabelToMultipleRequestHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelToMultipleRequestHandler(
                    new AddLabelToMultipleJSONDeserializer(
                        new IriOfferIdentifierJSONDeserializer(
                            $app['iri_offer_identifier_factory']
                        )
                    ),
                    $app['bulk_label_offer_command_bus']
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
