<?php

use CultuurNet\UDB3\Address\AddressServiceProvider;
use CultuurNet\UDB3\AMQP\AMQPConnectionServiceProvider;
use CultuurNet\UDB3\AMQP\AMQPPublisherServiceProvider;
use CultuurNet\UDB3\Authentication\AuthServiceProvider;
use CultuurNet\UDB3\Cache\CacheServiceProvider;
use CultuurNet\UDB3\Clock\ClockServiceProvider;
use CultuurNet\UDB3\CommandHandling\CommandBusServiceProvider;
use CultuurNet\UDB3\Configuration\ConfigurationServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorServiceProvider;
use CultuurNet\UDB3\Culturefeed\CultureFeedServiceProvider;
use CultuurNet\UDB3\Curators\CuratorsServiceProvider;
use CultuurNet\UDB3\Database\DatabaseServiceProvider;
use CultuurNet\UDB3\Error\SentryServiceProvider;
use CultuurNet\UDB3\Event\EventCommandHandlerProvider;
use CultuurNet\UDB3\Event\EventEditingServiceProvider;
use CultuurNet\UDB3\Event\EventGeoCoordinatesServiceProvider;
use CultuurNet\UDB3\Event\EventHistoryServiceProvider;
use CultuurNet\UDB3\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Event\EventPermissionServiceProvider;
use CultuurNet\UDB3\Event\EventRdfServiceProvider;
use CultuurNet\UDB3\Event\EventReadServiceProvider;
use CultuurNet\UDB3\Event\EventRequestHandlerServiceProvider;
use CultuurNet\UDB3\Event\EventServiceProvider;
use CultuurNet\UDB3\Event\ProductionServiceProvider;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventBus\EventBusServiceProvider;
use CultuurNet\UDB3\EventStore\EventStoreServiceProvider;
use CultuurNet\UDB3\Export\ExportServiceProvider;
use CultuurNet\UDB3\Geocoding\GeocodingServiceProvider;
use CultuurNet\UDB3\Jobs\JobsServiceProvider;
use CultuurNet\UDB3\Keycloak\KeycloakServiceProvider;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Mailer\MailerServiceProvider;
use CultuurNet\UDB3\Mailinglist\MailinglistServiceProvider;
use CultuurNet\UDB3\Media\ImageStorageProvider;
use CultuurNet\UDB3\Media\MediaImportServiceProvider;
use CultuurNet\UDB3\Media\MediaServiceProvider;
use CultuurNet\UDB3\Metadata\MetadataServiceProvider;
use CultuurNet\UDB3\Offer\BulkLabelOfferServiceProvider;
use CultuurNet\UDB3\Offer\OfferServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerCommandHandlerProvider;
use CultuurNet\UDB3\Organizer\OrganizerGeoCoordinatesServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerPermissionServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerRdfServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerRequestHandlerServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\Ownership\OwnershipCommandHandlerProvider;
use CultuurNet\UDB3\Ownership\OwnershipRequestHandlerServiceProvider;
use CultuurNet\UDB3\Ownership\OwnershipServiceProvider;
use CultuurNet\UDB3\Place\PlaceEditingServiceProvider;
use CultuurNet\UDB3\Place\PlaceGeoCoordinatesServiceProvider;
use CultuurNet\UDB3\Place\PlaceHistoryServiceProvider;
use CultuurNet\UDB3\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Place\PlacePermissionServiceProvider;
use CultuurNet\UDB3\Place\PlaceRdfServiceProvider;
use CultuurNet\UDB3\Place\PlaceReadServiceProvider;
use CultuurNet\UDB3\Place\PlaceRequestHandlerServiceProvider;
use CultuurNet\UDB3\Place\PlaceServiceProvider;
use CultuurNet\UDB3\RDF\RdfNamespaces;
use CultuurNet\UDB3\RDF\RdfServiceProvider;
use CultuurNet\UDB3\Role\RoleRequestHandlerServiceProvider;
use CultuurNet\UDB3\Role\RoleServiceProvider;
use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\SavedSearches\SavedSearchesServiceProvider;
use CultuurNet\UDB3\Search\Sapi3SearchServiceProvider;
use CultuurNet\UDB3\Security\GeneralSecurityServiceProvider;
use CultuurNet\UDB3\Security\OfferSecurityServiceProvider;
use CultuurNet\UDB3\Security\OrganizerSecurityServiceProvider;
use CultuurNet\UDB3\SwiftMailer\SwiftMailerServiceProvider;
use CultuurNet\UDB3\Cultuurkuur\CultuurkuurServiceProvider;
use CultuurNet\UDB3\Term\TermServiceProvider;
use CultuurNet\UDB3\UDB2\UDB2EventServicesProvider;
use CultuurNet\UDB3\UiTPAS\UiTPASIncomingEventServicesProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceEventServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceLabelsServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceOrganizerServiceProvider;
use CultuurNet\UDB3\User\UserServiceProvider;
use CultuurNet\UDB3\Uitwisselingsplatform\UitwisselingsplatformServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;

date_default_timezone_set('Europe/Brussels');

/**
 * Disable warnings for calling new SimpleXmlElement() with invalid XML.
 * An exception will still be thrown, but no warnings will be generated (which are hard to catch/hide otherwise).
 * We do this system-wide because we parse XML in various places (UiTPAS API responses, UiTID v1 responses, imported UDB2 XML, ...)
 */
libxml_use_internal_errors(true);

$container = new Container();
$container->delegate(new ReflectionContainer(true));

/** Supporting services */
$container->addServiceProvider(new ConfigurationServiceProvider());
$container->addServiceProvider(new EventStoreServiceProvider());
$container->addServiceProvider(new SentryServiceProvider());
$container->addServiceProvider(new CommandBusServiceProvider());
$container->addServiceProvider(new EventBusServiceProvider());
$container->addServiceProvider(new SwiftMailerServiceProvider());
$container->addServiceProvider(new ClockServiceProvider());
$container->addServiceProvider(new CacheServiceProvider());
$container->addServiceProvider(new DatabaseServiceProvider());
$container->addServiceProvider(new ContributorServiceProvider());

/** Queue */
$container->addServiceProvider(new AMQPConnectionServiceProvider());
$container->addServiceProvider(new AMQPPublisherServiceProvider());

/** Search */
$container->addServiceProvider(new SavedSearchesServiceProvider());

/** CultureFeed */
$container->addServiceProvider(new CultureFeedServiceProvider());

/** Security */
$container->addServiceProvider(new GeneralSecurityServiceProvider());
$container->addServiceProvider(new OfferSecurityServiceProvider());
$container->addServiceProvider(new OrganizerSecurityServiceProvider());

/** Event */
$container->addServiceProvider(new EventServiceProvider());
$container->addServiceProvider(new EventJSONLDServiceProvider());
$container->addServiceProvider(new EventEditingServiceProvider());
$container->addServiceProvider(new EventReadServiceProvider());
$container->addServiceProvider(new EventCommandHandlerProvider());
$container->addServiceProvider(new EventRequestHandlerServiceProvider());
$container->addServiceProvider(new EventPermissionServiceProvider());
$container->addServiceProvider(new ProductionServiceProvider());
$container->addServiceProvider(new EventGeoCoordinatesServiceProvider());
$container->addServiceProvider(new EventHistoryServiceProvider());

/** Place **/
$container->addServiceProvider(new PlaceServiceProvider());
$container->addServiceProvider(new PlaceJSONLDServiceProvider());
$container->addServiceProvider(new PlaceEditingServiceProvider());
$container->addServiceProvider(new PlaceReadServiceProvider());
$container->addServiceProvider(new PlaceRequestHandlerServiceProvider());
$container->addServiceProvider(new PlacePermissionServiceProvider());
$container->addServiceProvider(new PlaceGeoCoordinatesServiceProvider());
$container->addServiceProvider(new PlaceHistoryServiceProvider());

/** Organizer **/
$container->addServiceProvider(new OrganizerServiceProvider());
$container->addServiceProvider(new OrganizerRequestHandlerServiceProvider());
$container->addServiceProvider(new OrganizerJSONLDServiceProvider());
$container->addServiceProvider(new OrganizerCommandHandlerProvider());
$container->addServiceProvider(new OrganizerPermissionServiceProvider());
$container->addServiceProvider(new OrganizerGeoCoordinatesServiceProvider());

/** Roles */
$container->addServiceProvider(new RoleServiceProvider());
$container->addServiceProvider(new RoleRequestHandlerServiceProvider());

/** Metadata */
$container->addServiceProvider(new MetadataServiceProvider());

/** Export */
$container->addServiceProvider(new ExportServiceProvider());

/** User */
$container->addServiceProvider(new UserPermissionsServiceProvider());
$container->addServiceProvider(new UserServiceProvider());

/** Offer */
$container->addServiceProvider(new OfferServiceProvider());

/** Label */
$container->addServiceProvider(new LabelServiceProvider());
$container->addServiceProvider(new BulkLabelOfferServiceProvider());

/** Uitpas */
$container->addServiceProvider(new UiTPASServiceLabelsServiceProvider());
$container->addServiceProvider(new UiTPASServiceEventServiceProvider());
$container->addServiceProvider(new UiTPASServiceOrganizerServiceProvider());
$container->addServiceProvider(new UiTPASIncomingEventServicesProvider());

/** Media */
$container->addServiceProvider(new MediaServiceProvider());
$container->addServiceProvider(new ImageStorageProvider());
$container->addServiceProvider(new MediaImportServiceProvider());

/** Search */
$container->addServiceProvider(new Sapi3SearchServiceProvider());

/** Auth */
// Provides authentication of HTTP requests. While the HTTP authentication is not needed in CLI context, the service
// provider still needs to be registered in the general bootstrap.php instead of web/index.php so CLI commands have
// access to services like CurrentUser, which is also provided when an async job is being handled in the CLI and the
// user who triggered the job is being impersonated.
$container->addServiceProvider(new AuthServiceProvider());
$container->addServiceProvider(new KeycloakServiceProvider());

/** UDB2 */
$container->addServiceProvider(new UDB2EventServicesProvider());

/** Geocoding */
$container->addServiceProvider(new GeocodingServiceProvider());

/** Curators */
$container->addServiceProvider(new CuratorsServiceProvider());

/** Term */
$container->addServiceProvider(new TermServiceProvider());

/** Jobs */
$container->addServiceProvider(new JobsServiceProvider());

/** RDF */
RdfNamespaces::register();
$container->addServiceProvider(new RdfServiceProvider());
$container->addServiceProvider(new PlaceRdfServiceProvider());
$container->addServiceProvider(new EventRdfServiceProvider());
$container->addServiceProvider(new OrganizerRdfServiceProvider());

/** Ownership */
$container->addServiceProvider(new OwnershipServiceProvider());
$container->addServiceProvider(new OwnershipCommandHandlerProvider());
$container->addServiceProvider(new OwnershipRequestHandlerServiceProvider());

/** Mailinglist */
$container->addServiceProvider(new MailinglistServiceProvider());

$container->addServiceProvider(new CultuurkuurServiceProvider());
$container->addServiceProvider(new MailerServiceProvider());
$container->addServiceProvider(new UitwisselingsplatformServiceProvider());


/** Addresses */
$container->addServiceProvider(new AddressServiceProvider());

if (isset($container->get('config')['bookable_event']['dummy_place_ids'])) {
    LocationId::setDummyPlaceForEducationIds($container->get('config')['bookable_event']['dummy_place_ids']);
}

return $container;
