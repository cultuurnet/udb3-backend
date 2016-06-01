<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Cdb\Event\Not;
use CultuurNet\UDB3\UDB2\Actor\ActorEventApplier;
use CultuurNet\UDB3\UDB2\Actor\EventCdbXmlEnricher as ActorEventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Event\EventApplier;
use CultuurNet\UDB3\UDB2\Event\EventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Event\EventFactory;
use CultuurNet\UDB3\UDB2\LabeledAsUDB3Place;
use CultuurNet\UDB3\UDB2\Organizer\OrganizerFactory;
use CultuurNet\UDB3\UDB2\Organizer\QualifiesAsOrganizerSpecification;
use CultuurNet\UDB3\UDB2\Place\PlaceFromActorFactory;
use CultuurNet\UDB3\UDB2\Place\PlaceFromEventFactory;
use CultuurNet\UDB3\UDB2\Place\QualifiesAsPlaceSpecification;
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
        $app['cdbxml_enricher_http_client_adapter'] = $app->share(
            function (Application $app) {
                $handlerStack = new HandlerStack(\GuzzleHttp\choose_handler());
                $handlerStack->push(Middleware::prepareBody(), 'prepare_body');

                $responseTimeout = 3;
                $connectTimeout = 1;

                if (isset($app['udb2_cdbxml_enricher.http_response_timeout'])) {
                    $responseTimeout = $app['udb2_cdbxml_enricher.http_response_timeout'];
                }
                if (isset($app['udb2_cdbxml_enricher.http_connect_timeout'])) {
                    $connectTimeout = $app['udb2_cdbxml_enricher.http_connect_timeout'];
                }

                $client = new Client(
                    [
                        'handler' => $handlerStack,
                        'timeout' => $responseTimeout,
                        'connect_timeout' => $connectTimeout,
                    ]
                );

                return new ClientAdapter($client);
            }
        );

        $app['cdbxml_enricher_logger'] = $app->share(
            function (Application $app) {
                $logger = new \Monolog\Logger('udb2-events-cdbxml-enricher');
                $logger->pushHandler($app['udb2_log_handler']);
                return $logger;
            }
        );

        $app['udb2_events_cdbxml_enricher'] = $app->share(
            function (Application $app) {
                $enricher = new EventCdbXmlEnricher(
                    $app['event_bus'],
                    $app['cdbxml_enricher_http_client_adapter']
                );

                $enricher->setLogger($app['cdbxml_enricher_logger']);

                return $enricher;
            }
        );

        $app['udb2_actor_events_cdbxml_enricher'] = $app->share(
            function (Application $app) {
                $enricher = new ActorEventCdbXmlEnricher(
                    $app['event_bus'],
                    $app['cdbxml_enricher_http_client_adapter']
                );

                $enricher->setLogger($app['cdbxml_enricher_logger']);

                return $enricher;
            }
        );

        $app['udb2_events_to_udb3_place_applier'] = $app->share(
            function (Application $app) {
                $applier = new EventApplier(
                    new LabeledAsUDB3Place(),
                    $app['real_place_repository'],
                    new PlaceFromEventFactory()
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

        $app['udb2_actor_events_to_udb3_place_applier'] = $app->share(
            function (Application $app) {
                $applier = new ActorEventApplier(
                    $app['real_place_repository'],
                    new PlaceFromActorFactory(),
                    new QualifiesAsPlaceSpecification()
                );

                $logger = new \Monolog\Logger('udb2-actor-events-to-udb3-organizer-applier');
                $logger->pushHandler($app['udb2_log_handler']);

                $applier->setLogger($logger);

                return $applier;
            }
        );

        $app['udb2_actor_events_to_udb3_organizer_applier'] = $app->share(
            function (Application $app) {
                $applier = new ActorEventApplier(
                    $app['real_organizer_repository'],
                    new OrganizerFactory(),
                    new QualifiesAsOrganizerSpecification()
                );

                $logger = new \Monolog\Logger('udb2-actor-events-to-udb3-organizer-applier');
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
