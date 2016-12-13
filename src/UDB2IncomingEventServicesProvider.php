<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\BroadwayAMQP\EventBusForwardingConsumerFactory;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB2DomainEvents\ActorCreatedJSONDeserializer;
use CultuurNet\UDB2DomainEvents\ActorUpdatedJSONDeserializer;
use CultuurNet\UDB2DomainEvents\EventCreatedJSONDeserializer;
use CultuurNet\UDB2DomainEvents\EventUpdatedJSONDeserializer;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\Event\Not;
use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use CultuurNet\UDB3\UDB2\Actor\ActorEventApplier;
use CultuurNet\UDB3\UDB2\Actor\ActorEventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Actor\ActorToUDB3OrganizerFactory;
use CultuurNet\UDB3\UDB2\Actor\ActorToUDB3PlaceFactory;
use CultuurNet\UDB3\UDB2\Actor\Specification\QualifiesAsOrganizerSpecification;
use CultuurNet\UDB3\UDB2\Actor\Specification\QualifiesAsPlaceSpecification;
use CultuurNet\UDB3\UDB2\Event\EventApplier;
use CultuurNet\UDB3\UDB2\Event\EventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Event\EventToUDB3EventFactory;
use CultuurNet\UDB3\UDB2\Event\EventToUDB3PlaceFactory;
use CultuurNet\UDB3\UDB2\Label\LabelImporter;
use CultuurNet\UDB3\UDB2\LabeledAsUDB3Place;
use CultuurNet\UDB3\UDB2\OfferToSapiUrlTransformer;
use CultuurNet\UDB3\UDB2\XSD\CachedInMemoryXSDReader;
use CultuurNet\UDB3\UDB2\XSD\FileGetContentsXSDReader;
use CultuurNet\UDB3\UDB2\XSD\XSDAwareXMLValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Http\Adapter\Guzzle6\Client as ClientAdapter;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;
use ValueObjects\String\String as StringLiteral;

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

        $importValidateXml = $toggles->active(
            'import-validate-xml',
            $app['toggles.context']
        );

        $app['udb2_log_handler'] = $app->share(
            function (Application $app) {
                return new \Monolog\Handler\StreamHandler(__DIR__ . '/../log/udb2.log');
            }
        );

        $app['udb2_deserializer_locator'] = $app->share(
            function (Application $app) {
                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.udb2-events.actor-created+json'
                    ),
                    new ActorCreatedJSONDeserializer()
                );
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.udb2-events.actor-updated+json'
                    ),
                    new ActorUpdatedJSONDeserializer()
                );
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.udb2-events.event-created+json'
                    ),
                    new EventCreatedJSONDeserializer()
                );
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.udb2-events.event-updated+json'
                    ),
                    new EventUpdatedJSONDeserializer()
                );
                return $deserializerLocator;
            }
        );

        $app['udb2_event_bus_forwarding_consumer_factory'] = $app->share(
            function (Application $app) {
                return new EventBusForwardingConsumerFactory(
                    $app['amqp-execution-delay'],
                    $app['amqp.connection'],
                    $app['logger.amqp.event_bus_forwarder'],
                    $app['udb2_deserializer_locator'],
                    $app['event_bus'],
                    new StringLiteral($app['config']['amqp']['consumer_tag'])
                );
            }
        );

        $app['amqp.udb2_event_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                $consumerConfig = $app['config']['amqp']['consumers']['udb2'];
                $exchange = new StringLiteral($consumerConfig['exchange']);
                $queue = new StringLiteral($consumerConfig['queue']);

                /** @var EventBusForwardingConsumerFactory $consumerFactory */
                $consumerFactory = $app['udb2_event_bus_forwarding_consumer_factory'];

                return $consumerFactory->create($exchange, $queue);
            }
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

        $app['cdbxml_enricher_xml_validation_service'] = $app->share(
            function (Application $app) {
                $reader = new CachedInMemoryXSDReader(
                    new FileGetContentsXSDReader($app['udb2_cdbxml_enricher.xsd'])
                );

                return new XSDAwareXMLValidationService($reader, LIBXML_ERR_ERROR);
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
            function (Application $app) use ($importFromSapi, $importValidateXml) {
                $xmlValidationService = null;
                if ($importValidateXml) {
                    $xmlValidationService = $app['cdbxml_enricher_xml_validation_service'];
                }

                $enricher = new EventCdbXmlEnricher(
                    $app['event_bus'],
                    $app['cdbxml_enricher_http_client_adapter'],
                    $xmlValidationService
                );

                $enricher->setLogger($app['cdbxml_enricher_logger']);

                if ($importFromSapi) {
                    $eventUrlFormat = $app['udb2_cdbxml_enricher.event_url_format'];
                    if (is_null($eventUrlFormat)) {
                        throw new \Exception('can not import events from sapi without configuring an url format');
                    }
                    $transformer = new OfferToSapiUrlTransformer($eventUrlFormat);
                    $enricher->withUrlTransformer($transformer);
                }

                return $enricher;
            }
        );

        $app['udb2_actor_events_cdbxml_enricher'] = $app->share(
            function (Application $app) use ($importFromSapi, $importValidateXml) {
                $xmlValidationService = null;
                if ($importValidateXml) {
                    $xmlValidationService = $app['cdbxml_enricher_xml_validation_service'];
                }

                $enricher = new ActorEventCdbXmlEnricher(
                    $app['event_bus'],
                    $app['cdbxml_enricher_http_client_adapter'],
                    $xmlValidationService
                );

                $enricher->setLogger($app['cdbxml_enricher_logger']);

                if ($importFromSapi) {
                    $actorUrlFormat = $app['udb2_cdbxml_enricher.actor_url_format'];
                    if (is_null($actorUrlFormat)) {
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
                    $app['place_repository'],
                    new EventToUDB3PlaceFactory()
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
                    $app['event_repository'],
                    new EventToUDB3EventFactory()
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
                    $app['place_repository'],
                    new ActorToUDB3PlaceFactory(),
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
                    $app['organizer_repository'],
                    new ActorToUDB3OrganizerFactory(),
                    new QualifiesAsOrganizerSpecification()
                );

                $logger = new \Monolog\Logger('udb2-actor-events-to-udb3-organizer-applier');
                $logger->pushHandler($app['udb2_log_handler']);

                $applier->setLogger($logger);

                return $applier;
            }
        );

        $app['udb2_label_importer'] = $app->share(
            function (Application $app) {
                $labelImporter = new LabelImporter(
                    $app['labels.constraint_aware_service']
                );

                $logger = new \Monolog\Logger('udb2-label-importer');
                $logger->pushHandler($app['udb2_log_handler']);
                $labelImporter->setLogger($logger);

                return $labelImporter;
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
