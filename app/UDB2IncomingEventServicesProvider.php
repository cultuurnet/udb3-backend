<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\UDB2\DomainEvents\ActorCreatedJSONDeserializer;
use CultuurNet\UDB3\UDB2\DomainEvents\ActorUpdatedJSONDeserializer;
use CultuurNet\UDB3\UDB2\DomainEvents\EventCreatedJSONDeserializer;
use CultuurNet\UDB3\UDB2\DomainEvents\EventUpdatedJSONDeserializer;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\Event\Any;
use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use CultuurNet\UDB3\UDB2\Actor\ActorImporter;
use CultuurNet\UDB3\UDB2\Actor\ActorEventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Actor\ActorToUDB3OrganizerFactory;
use CultuurNet\UDB3\UDB2\Actor\ActorToUDB3PlaceFactory;
use CultuurNet\UDB3\UDB2\Actor\Specification\QualifiesAsOrganizerSpecification;
use CultuurNet\UDB3\UDB2\Actor\Specification\QualifiesAsPlaceSpecification;
use CultuurNet\UDB3\UDB2\Event\EventImporter;
use CultuurNet\UDB3\UDB2\Event\EventCdbXmlEnricher;
use CultuurNet\UDB3\UDB2\Event\EventXMLValidatorService;
use CultuurNet\UDB3\UDB2\Label\LabelImporter;
use CultuurNet\UDB3\UDB2\Media\ImageCollectionFactory;
use CultuurNet\UDB3\UDB2\Media\MediaImporter;
use CultuurNet\UDB3\UDB2\XML\CompositeXmlValidationService;
use CultuurNet\UDB3\UDB2\XSD\CachedInMemoryXSDReader;
use CultuurNet\UDB3\UDB2\XSD\FileGetContentsXSDReader;
use CultuurNet\UDB3\UDB2\XSD\XSDAwareXMLValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Http\Adapter\Guzzle7\Client as ClientAdapter;
use Monolog\Handler\StreamHandler;
use Ramsey\Uuid\UuidFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\StringLiteral;

class UDB2IncomingEventServicesProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['udb2_deserializer_locator'] = $app->share(
            function () {
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
                $logger = LoggerFactory::create(
                    $app,
                    LoggerName::forAmqpWorker('xml-imports', 'messages'),
                    [new StreamHandler('php://stdout')]
                );

                return new EventBusForwardingConsumerFactory(
                    0,
                    $app['amqp.connection'],
                    $logger,
                    $app['udb2_deserializer_locator'],
                    $app[EventBus::class],
                    new StringLiteral($app['config']['amqp']['consumer_tag']),
                    new UuidFactory()
                );
            }
        );

        $app['amqp.udb2_event_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                // If this service gets instantiated, it's because we're running the AMQP listener for CDBXML imports so
                // we should set the API name to CDBXML.
                $app['api_name'] = ApiName::CDBXML;

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

        $app['xsd_validation_service'] = $app->share(
            function (Application $app) {
                $reader = new CachedInMemoryXSDReader(
                    new FileGetContentsXSDReader($app['udb2_cdbxml_enricher.xsd'])
                );

                return new XSDAwareXMLValidationService($reader, LIBXML_ERR_ERROR);
            }
        );

        $app['event_cdbxml_enricher_xml_validation_service'] = $app->share(
            function (Application $app) {
                return new CompositeXmlValidationService(
                    $app['xsd_validation_service'],
                    new EventXMLValidatorService(
                        new StringLiteral('http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL')
                    )
                );
            }
        );

        $app['actor_cdbxml_enricher_xml_validation_service'] = $app->share(
            function (Application $app) {
                return new CompositeXmlValidationService(
                    $app['xsd_validation_service']
                );
            }
        );

        $app['logger.xml-imports.enricher'] = $app->share(
            function (Application $app) {
                return LoggerFactory::create($app, LoggerName::forAmqpWorker('xml-imports', 'enricher'));
            }
        );

        $app['udb2_events_cdbxml_enricher'] = $app->share(
            function (Application $app) {
                $enricher = new EventCdbXmlEnricher(
                    $app[EventBus::class],
                    $app['cdbxml_enricher_http_client_adapter'],
                    new UuidFactory(),
                    $app['event_cdbxml_enricher_xml_validation_service']
                );

                $enricher->setLogger($app['logger.xml-imports.enricher']);

                return $enricher;
            }
        );

        $app['udb2_actor_events_cdbxml_enricher'] = $app->share(
            function (Application $app) {
                $enricher = new ActorEventCdbXmlEnricher(
                    $app[EventBus::class],
                    $app['cdbxml_enricher_http_client_adapter'],
                    new UuidFactory(),
                    $app['actor_cdbxml_enricher_xml_validation_service']
                );

                $enricher->setLogger($app['logger.xml-imports.enricher']);

                return $enricher;
            }
        );

        $app['udb2_events_to_udb3_event_applier'] = $app->share(
            function (Application $app) {
                $applier = new EventImporter(
                    new Any(),
                    $app['event_repository'],
                    $app['udb2_media_importer'],
                    $app['related_udb3_labels_applier'],
                    $app['udb2_event_cdbid_extractor'],
                    $app['event_command_bus']
                );

                $applier->setLogger(
                    LoggerFactory::create($app, LoggerName::forAmqpWorker('xml-imports', 'event'))
                );

                return $applier;
            }
        );

        $app['udb2_actor_events_to_udb3_place_applier'] = $app->share(
            function (Application $app) {
                $applier = new ActorImporter(
                    $app['place_repository'],
                    new ActorToUDB3PlaceFactory(),
                    new QualifiesAsPlaceSpecification(),
                    $app['related_udb3_labels_applier'],
                    $app['udb2_media_importer']
                );

                $applier->setLogger(
                    LoggerFactory::create($app, LoggerName::forAmqpWorker('xml-imports', 'place'))
                );

                return $applier;
            }
        );

        $app['udb2_actor_events_to_udb3_organizer_applier'] = $app->share(
            function (Application $app) {
                $applier = new ActorImporter(
                    $app['organizer_repository'],
                    new ActorToUDB3OrganizerFactory(),
                    new QualifiesAsOrganizerSpecification(),
                    $app['related_udb3_labels_applier']
                );

                $applier->setLogger(
                    LoggerFactory::create($app, LoggerName::forAmqpWorker('xml-imports', 'organizer'))
                );

                return $applier;
            }
        );

        $app['udb2_label_importer'] = $app->share(
            function (Application $app) {
                $labelImporter = new LabelImporter(
                    $app['labels.constraint_aware_service']
                );

                $labelImporter->setLogger(LoggerFactory::create($app, LoggerName::forAmqpWorker('xml-imports', 'labels')));

                return $labelImporter;
            }
        );

        $app['udb2_media_importer'] = $app->share(
            function (Application $app) {
                $mediaImporter = new MediaImporter(
                    $app['media_manager'],
                    (new ImageCollectionFactory())->withUuidRegex($app['udb2_cdbxml_enricher.media_uuid_regex'])
                );

                $mediaImporter->setLogger(
                    LoggerFactory::create($app, LoggerName::forAmqpWorker('xml-imports', 'media'))
                );

                return $mediaImporter;
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
                $mappingFileLocation = $app['udb2_place_external_id_mapping.file_location'];
                return $app['udb2_external_id_mapping_service_factory']($mappingFileLocation);
            }
        );

        $app['udb2_organizer_external_id_mapping_service'] = $app->share(
            function (Application $app) {
                $mappingFileLocation = $app['udb2_organizer_external_id_mapping.file_location'];
                return $app['udb2_external_id_mapping_service_factory']($mappingFileLocation);
            }
        );

        $app['udb2_external_id_mapping_service_factory'] = $app->protect(
            function ($mappingFileLocation) {
                $map = [];

                if (file_exists($mappingFileLocation)) {
                    $mapping = require $mappingFileLocation;

                    if (is_array($mapping)) {
                        $map = $mapping;
                    }
                }

                return new ArrayMappingService($map);
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
