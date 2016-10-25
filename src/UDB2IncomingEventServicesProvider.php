<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\Event\Not;
use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use CultuurNet\UDB3\UDB2\Actor\ActorEventApplier;
use CultuurNet\UDB3\UDB2\Actor\EventCdbXmlEnricher as ActorEventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Event\EventApplier;
use CultuurNet\UDB3\UDB2\Event\EventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Event\EventFactory;
use CultuurNet\UDB3\UDB2\LabeledAsUDB3Place;
use CultuurNet\UDB3\UDB2\OfferToSapiUrlTransformer;
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
use Symfony\Component\Yaml\Yaml;

class UDB2IncomingEventServicesProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        /** @var \Qandidate\Toggle\ToggleManager $toggles */
        $toggles = $app['toggles'];

        $importFromSapi = $toggles->active(
            'import-from-sapi',
            $app['toggles.context']
        );

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
            function (Application $app) use ($importFromSapi) {
                $enricher = new EventCdbXmlEnricher(
                    $app['event_bus'],
                    $app['cdbxml_enricher_http_client_adapter']
                );

                $enricher->setLogger($app['cdbxml_enricher_logger']);

                if ($importFromSapi) {
                    $eventUrlFormat = $app['config']['udb2_import']['event_url_format'];
                    if (!isset($eventUrlFormat)) {
                        throw new \Exception('can not import events from sapi without configuring an url format');
                    }
                    $transformer = new OfferToSapiUrlTransformer($eventUrlFormat);
                    $enricher->withUrlTransformer($transformer);
                }

                return $enricher;
            }
        );

        $app['udb2_actor_events_cdbxml_enricher'] = $app->share(
            function (Application $app) use ($importFromSapi) {
                $enricher = new ActorEventCdbXmlEnricher(
                    $app['event_bus'],
                    $app['cdbxml_enricher_http_client_adapter']
                );

                $enricher->setLogger($app['cdbxml_enricher_logger']);

                if ($importFromSapi) {
                    $actorUrlFormat = $app['config']['udb2_import']['actor_url_format'];
                    if (!isset($actorUrlFormat)) {
                        throw new \Exception('can not import actors from sapi without configuring an url format');
                    }
                    $transformer = new OfferToSapiUrlTransformer($actorUrlFormat);
                    $enricher->withUrlTransformer($transformer);
                }

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

                $logger = new \Monolog\Logger('udb2-actor-events-to-udb3-place-applier');
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

        $app['udb2_event_cdbid_extractor'] = $app->share(
            function (Application $app) {
                return new EventCdbIdExtractor(
                    $app['udb2_place_external_id_mapping_service'],
                    $app['udb2_organizer_external_id_mapping_service']
                );
            }
        );

        $app['udb2_place_external_id_mapping_service'] = $app->share(
            function (Application $app) {
                $yamlFileLocation = $app['udb2_place_external_id_mapping.yml_file_location'];
                return $app['udb2_external_id_mapping_service_factory']($yamlFileLocation);
            }
        );

        $app['udb2_organizer_external_id_mapping_service'] = $app->share(
            function (Application $app) {
                $yamlFileLocation = $app['udb2_organizer_external_id_mapping.yml_file_location'];
                return $app['udb2_external_id_mapping_service_factory']($yamlFileLocation);
            }
        );

        $app['udb2_external_id_mapping_service_factory'] = $app->protect(
            function ($yamlFileLocation) {
                $map = [];

                if (file_exists($yamlFileLocation)) {
                    $yaml = file_get_contents($yamlFileLocation);
                    $yaml = Yaml::parse($yaml);

                    if (is_array($yaml)) {
                        $map = $yaml;
                    }
                }

                return new ArrayMappingService($map);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
