<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Cdb\Event\Not;
use CultuurNet\UDB3\UDB2\Event\EventApplier;
use CultuurNet\UDB3\UDB2\Event\EventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Event\EventFactory;
use CultuurNet\UDB3\UDB2\LabeledAsUDB3Place;
use CultuurNet\UDB3\UDB2\Place\PlaceFactory;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Http\Adapter\Guzzle6\Client as ClientAdapter;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UDB2IncomingEventServicesProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['udb2_events_cdbxml_enricher'] = $app->share(
            function (Application $app) {
                $handlerStack = new HandlerStack(\GuzzleHttp\choose_handler());
                $handlerStack->push(Middleware::prepareBody(), 'prepare_body');
                $client = new Client(
                    [
                        'handler' => $handlerStack,
                        'timeout' => 3,
                        'connect_timeout' => 1,
                    ]
                );

                $enricher = new EventCdbXmlEnricher(
                    $app['event_bus'],
                    new ClientAdapter($client)
                );

                $logger = new \Monolog\Logger('udb2-events-cdbxml-enricher');
                $logger->pushHandler($app['udb2_log_handler']);

                $enricher->setLogger($logger);

                return $enricher;
            }
        );

        $app['udb2_events_to_udb3_place_applier'] = $app->share(
            function (Application $app) {
                $applier = new EventApplier(
                    new LabeledAsUDB3Place(),
                    $app['real_place_repository'],
                    new PlaceFactory()
                );

                $logger = new \Monolog\Logger('udb2-events-to-udb3-place-applier');
                $logger->pushHandler($app['udb2_log_handler']);

                $applier->setLogger($logger);

                return $applier;
            }
        );

        $app['udb2_events_to_udb3_event_applier'] = $app->share(
            function (Application $app) {
                $applier = new EventApplier(
                    new Not(new LabeledAsUDB3Place()),
                    $app['real_event_repository'],
                    new EventFactory()
                );

                $logger = new \Monolog\Logger('udb2-events-to-udb3-event-applier');
                $logger->pushHandler($app['udb2_log_handler']);

                $applier->setLogger($logger);

                return $applier;
            }
        );
    }

    public function boot(Application $app)
    {

    }
}
