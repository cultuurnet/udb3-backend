<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumerFactory;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
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
