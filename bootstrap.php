<?php

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\AMQP\AMQPConnectionServiceProvider;
use CultuurNet\UDB3\AMQP\AMQPPublisherServiceProvider;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Auth0\Auth0ServiceProvider;
use CultuurNet\UDB3\Authentication\AuthServiceProvider;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\Clock\SystemClock;
use CultuurNet\UDB3\CommandHandling\CommandBusServiceProvider;
use CultuurNet\UDB3\Culturefeed\CultureFeedServiceProvider;
use CultuurNet\UDB3\Curators\CuratorsServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Error\SentryServiceProvider;
use CultuurNet\UDB3\Event\EventCommandHandlerProvider;
use CultuurNet\UDB3\Event\EventEditingServiceProvider;
use CultuurNet\UDB3\Event\EventGeoCoordinatesServiceProvider;
use CultuurNet\UDB3\Event\EventHistoryServiceProvider;
use CultuurNet\UDB3\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Event\EventPermissionServiceProvider;
use CultuurNet\UDB3\Event\EventReadServiceProvider;
use CultuurNet\UDB3\Event\EventRequestHandlerServiceProvider;
use CultuurNet\UDB3\Event\ProductionServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventBus\EventBusServiceProvider;
use CultuurNet\UDB3\EventSourcing\DBAL\AggregateAwareDBALEventStore;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Jobs\JobsServiceProvider;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Log\SocketIOEmitterHandler;
use CultuurNet\UDB3\Metadata\MetadataServiceProvider;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Organizer\OrganizerCommandHandlerProvider;
use CultuurNet\UDB3\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerRequestHandlerServiceProvider;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\Organizer\WebsiteUniqueConstraintService;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\Role\RoleRequestHandlerServiceProvider;
use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Security\GeneralSecurityServiceProvider;
use CultuurNet\UDB3\Security\OfferSecurityServiceProvider;
use CultuurNet\UDB3\Security\OrganizerSecurityServiceProvider;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Silex\Container\PimplePSRContainerBridge;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Media\ImageStorageProvider;
use CultuurNet\UDB3\Place\PlaceHistoryServiceProvider;
use CultuurNet\UDB3\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Place\PlaceRequestHandlerServiceProvider;
use CultuurNet\UDB3\Search\Sapi3SearchServiceProvider;
use CultuurNet\UDB3\SwiftMailer\SwiftMailerServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceEventServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceLabelsServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceOrganizerServiceProvider;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Term\TermServiceProvider;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use League\Container\Argument\Literal\StringArgument;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Logger;
use Silex\Application;
use SocketIO\Emitter;

date_default_timezone_set('Europe/Brussels');

/**
 * Disable warnings for calling new SimpleXmlElement() with invalid XML.
 * An exception will still be thrown, but no warnings will be generated (which are hard to catch/hide otherwise).
 * We do this system-wide because we parse XML in various places (UiTPAS API responses, UiTID v1 responses, imported UDB2 XML, ...)
 */
libxml_use_internal_errors(true);

/**
 * Set up a PSR-11 container using league/container. The goal is for this container to replace the Silex Application
 * object (a Pimple container).
 * We inject this new PSR container into the Silex application (extended via HybridContainerApplication) so that Silex
 * service definitions can fetch services from the PSR container (if they exist there) instead of the Silex container.
 * We then wrap the Silex container in a decorator that makes it PSR-11 compatible and set that as a delegate on the
 * league container so that service definitions in the league container can fetch services from the Silex container if
 * they do not exist in the league container.
 * Lastly we set a ReflectionContainer as a second delegate on the league container to enable auto-wiring in the league
 * container. Because the Silex container also looks up missing services in the league container, it also gets auto-
 * wiring this way.
 */
$container = new Container();
$app = new HybridContainerApplication($container);
$container->delegate(new PimplePSRContainerBridge($app));
$container->delegate(new ReflectionContainer(true));

$container->addServiceProvider(new \CultuurNet\UDB3\Configuration\ConfigurationServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\EventStore\EventStoreServiceProvider());

$container->addServiceProvider(new SentryServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\SavedSearches\SavedSearchesServiceProvider());

$container->addServiceProvider(new CommandBusServiceProvider());
$container->addServiceProvider(new EventBusServiceProvider());

/**
 * CultureFeed services.
 */
$container->addServiceProvider(new CultureFeedServiceProvider());

/**
 * Mailing service.
 */
$container->addServiceProvider(new SwiftMailerServiceProvider());

$app['timezone'] = $app->share(
    function (Application $app) {
        $timezoneName = empty($app['config']['timezone']) ? 'Europe/Brussels' : $app['config']['timezone'];

        return new DateTimeZone($timezoneName);
    }
);

$app['clock'] = $app->share(
    function (Application $app) {
        return new SystemClock(
            $app['timezone']
        );
    }
);

$app['uuid_generator'] = $app->share(
    function () {
        return new \Broadway\UuidGenerator\Rfc4122\Version4Generator();
    }
);

$container->addServiceProvider(new GeneralSecurityServiceProvider());
$container->addServiceProvider(new OfferSecurityServiceProvider());
$container->addServiceProvider(new OrganizerSecurityServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Cache\CacheServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Database\DatabaseServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Event\EventServiceProvider());

$container->addServiceProvider(new EventJSONLDServiceProvider());

$app['calendar_factory'] = $app->share(
    function () {
        return new CalendarFactory();
    }
);

$app['cdbxml_contact_info_importer'] = $app->share(
    function () {
        return new CdbXmlContactInfoImporter();
    }
);

$app['logger_factory.resque_worker'] = $app::protect(
    function ($queueName) use ($app) {
        $redisConfig = [
            'host' => '127.0.0.1',
            'port' => 6379,
        ];
        if (extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect(
                $redisConfig['host'],
                $redisConfig['port']
            );
        } else {
            $redis = new Predis\Client(
                [
                    'host' => $redisConfig['host'],
                    'port' => $redisConfig['port']
                ]
            );
            $redis->connect();
        }
        $socketIOHandler = new SocketIOEmitterHandler(new Emitter($redis), Logger::INFO);

        return LoggerFactory::create($app->getLeagueContainer(), LoggerName::forResqueWorker($queueName), [$socketIOHandler]);
    }
);

/** Production */


/** Place **/
$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlaceServiceProvider());
$container->addServiceProvider(new PlaceJSONLDServiceProvider());

/** Organizer **/
$container->addServiceProvider(new \CultuurNet\UDB3\Organizer\OrganizerServiceProvider());
$container->addServiceProvider(new OrganizerRequestHandlerServiceProvider());
$container->addServiceProvider(new OrganizerJSONLDServiceProvider());
$container->addServiceProvider(new OrganizerCommandHandlerProvider());

/** Roles */
$container->addServiceProvider(new \CultuurNet\UDB3\Role\RoleServiceProvider());

$container->addServiceProvider(
    new AMQPConnectionServiceProvider()
);

$container->addServiceProvider(
    new AMQPPublisherServiceProvider()
);

$container->addServiceProvider(new MetadataServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Export\ExportServiceProvider());
$container->addServiceProvider(new EventEditingServiceProvider());
$container->addServiceProvider(new EventReadServiceProvider());
$container->addServiceProvider(new EventCommandHandlerProvider());
$container->addServiceProvider(new EventRequestHandlerServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlaceEditingServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlaceReadServiceProvider());
$container->addServiceProvider(new PlaceRequestHandlerServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\User\UserServiceProvider());
$container->addServiceProvider(new EventPermissionServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlacePermissionServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Organizer\OrganizerPermissionServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Offer\OfferServiceProvider());
$container->addServiceProvider(new LabelServiceProvider());
$container->addServiceProvider(new RoleRequestHandlerServiceProvider());
$container->addServiceProvider(new UserPermissionsServiceProvider());
$container->addServiceProvider(new ProductionServiceProvider());
$container->addServiceProvider(new UiTPASServiceLabelsServiceProvider());
$container->addServiceProvider(new UiTPASServiceEventServiceProvider());
$container->addServiceProvider(new UiTPASServiceOrganizerServiceProvider());

$container->addServiceProvider(
    new \CultuurNet\UDB3\Media\MediaServiceProvider()
);

$container->addServiceProvider(new ImageStorageProvider());

$container->addServiceProvider(new Sapi3SearchServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Offer\BulkLabelOfferServiceProvider());

// Provides authentication of HTTP requests. While the HTTP authentication is not needed in CLI context, the service
// provider still needs to be registered in the general bootstrap.php instead of web/index.php so CLI commands have
// access to services like CurrentUser, which is also provided when an async job is being handled in the CLI and the
// user who triggered the job is being impersonated.
$container->addServiceProvider(new AuthServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\UDB2\UDB2EventServicesProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\UiTPAS\UiTPASIncomingEventServicesProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Geocoding\GeocodingServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlaceGeoCoordinatesServiceProvider());
$container->addServiceProvider(new EventGeoCoordinatesServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Organizer\OrganizerGeoCoordinatesServiceProvider());

$container->addServiceProvider(new EventHistoryServiceProvider());
$container->addServiceProvider(new PlaceHistoryServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Media\MediaImportServiceProvider());

$container->addServiceProvider(new CuratorsServiceProvider());

$container->addServiceProvider(new Auth0ServiceProvider());

$container->addServiceProvider(new TermServiceProvider());

$container->addServiceProvider(new JobsServiceProvider());

if (isset($container->get('config')['bookable_event']['dummy_place_ids'])) {
    LocationId::setDummyPlaceForEducationIds($app['config']['bookable_event']['dummy_place_ids']);
}

return $container;
