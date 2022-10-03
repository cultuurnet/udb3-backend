#!/usr/bin/env php
<?php

use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Silex\Console\ChangeOfferOwner;
use CultuurNet\UDB3\Silex\Console\ChangeOfferOwnerInBulk;
use CultuurNet\UDB3\Silex\Console\ChangeOrganizerOwner;
use CultuurNet\UDB3\Silex\Console\ChangeOrganizerOwnerInBulk;
use CultuurNet\UDB3\Silex\Console\ConsumeCommand;
use CultuurNet\UDB3\Silex\Console\EventAncestorsCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Silex\Console\GeocodeEventCommand;
use CultuurNet\UDB3\Silex\Console\GeocodeOrganizerCommand;
use CultuurNet\UDB3\Silex\Console\GeocodePlaceCommand;
use CultuurNet\UDB3\Silex\Console\ImportOfferAutoClassificationLabels;
use CultuurNet\UDB3\Silex\Console\ProcessDuplicatePlaces;
use CultuurNet\UDB3\Silex\Console\PurgeModelCommand;
use CultuurNet\UDB3\Silex\Console\ReindexEventsWithRecommendations;
use CultuurNet\UDB3\Silex\Console\ReindexOffersWithPopularityScore;
use CultuurNet\UDB3\Silex\Console\RemoveFacilitiesFromPlace;
use CultuurNet\UDB3\Silex\Console\RemoveLabelOffer;
use CultuurNet\UDB3\Silex\Console\RemoveLabelOrganizer;
use CultuurNet\UDB3\Silex\Console\ReplaceNewsArticlePublisher;
use CultuurNet\UDB3\Silex\Console\ReplayCommand;
use CultuurNet\UDB3\Silex\Console\UpdateBookingAvailabilityCommand;
use CultuurNet\UDB3\Silex\Console\UpdateEventsAttendanceMode;
use CultuurNet\UDB3\Silex\Console\UpdateOfferStatusCommand;
use CultuurNet\UDB3\Silex\Console\UpdateUniqueLabels;
use CultuurNet\UDB3\Silex\Console\UpdateUniqueOrganizers;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Silex\Error\CliErrorHandlerProvider;
use CultuurNet\UDB3\Error\ErrorLogger;
use CultuurNet\UDB3\Silex\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

const API_NAME = ApiName::CLI;

/** @var HybridContainerApplication $app */
$app = require __DIR__ . '/../bootstrap.php';
$container = $app->getLeagueContainer();

$app->register(new CliErrorHandlerProvider());

$consoleApp = new Application('UDB3');
$consoleApp->setCatchExceptions(false);

// An udb3 system user is needed for geocode commands and updating the status of one or multiple offers.
// Because of the changes for geocoding the amqp forwarding for udb2 imports also needs a user.
// To avoid fixing this locally in the amqp-silex lib, all CLI commands are executed as udb3 system user.
$app['impersonator']->impersonate(
    new Metadata(
        [
            'user_id' => $app['system_user_id'],
        ]
    )
);

$heartBeat = static function () use ($container) {
    /** @var Connection $db */
    $db = $container->get('dbal_connection');
    $db->query('SELECT 1')->execute();
};

$consoleApp->add(
    new ConsumeCommand('amqp-listen-uitpas', 'amqp.uitpas_event_bus_forwarding_consumer', $container, $heartBeat)
);

$consoleApp->add(new ReplayCommand($container->get('event_command_bus'), $container->get('dbal_connection'), $container->get('eventstore_payload_serializer'), $container->get(EventBus::class)));
$consoleApp->add(new EventAncestorsCommand($container->get('event_command_bus'), $container->get('event_store')));
$consoleApp->add(new PurgeModelCommand($container->get('dbal_connection')));
$consoleApp->add(new GeocodePlaceCommand($container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_PLACES), $container->get('place_jsonld_repository')));
$consoleApp->add(new GeocodeEventCommand($container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS), $container->get('event_jsonld_repository')));
$consoleApp->add(new GeocodeOrganizerCommand($container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_ORGANIZERS), $container->get('organizer_jsonld_repository')));
$consoleApp->add(new FireProjectedToJSONLDForRelationsCommand($container->get(EventBus::class), $container->get('dbal_connection'), $container->get(OrganizerJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY), $container->get(PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY)));
$consoleApp->add(new FireProjectedToJSONLDCommand($container->get(EventBus::class), $container->get(OrganizerJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY), $container->get(PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY)));
$consoleApp->add(
    new ProcessDuplicatePlaces(
        $container->get('event_command_bus'),
        $container->get('duplicate_place_repository'),
        $container->get('canonical_service'),
        $container->get(EventBus::class),
        $container->get(PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY),
        $container->get(EventRelationsRepository::class),
        $container->get('dbal_connection')
    )
);
$consoleApp->add(
    new ReindexOffersWithPopularityScore(
        OfferType::event(),
        $container->get('dbal_connection'),
        $container->get(EventBus::class),
        $container->get(EventJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY)
    )
);
$consoleApp->add(
    new ReindexOffersWithPopularityScore(
        OfferType::place(),
        $container->get('dbal_connection'),
        $container->get(EventBus::class),
        $container->get(PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY)
    )
);
$consoleApp->add(
    new ReindexEventsWithRecommendations(
        $container->get('dbal_connection'),
        $container->get(EventBus::class),
        $container->get(EventJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY)
    )
);
$consoleApp->add(new UpdateOfferStatusCommand(OfferType::event(), $container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS)));
$consoleApp->add(new UpdateOfferStatusCommand(OfferType::place(), $container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_PLACES)));
$consoleApp->add(new UpdateBookingAvailabilityCommand($container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS)));
$consoleApp->add(new UpdateEventsAttendanceMode($container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS)));
$consoleApp->add(new ChangeOfferOwner($container->get('event_command_bus')));
$consoleApp->add(new ChangeOfferOwnerInBulk($container->get('event_command_bus'), $container->get('offer_owner_query')));
$consoleApp->add(new ChangeOrganizerOwner($container->get('event_command_bus')));
$consoleApp->add(new ChangeOrganizerOwnerInBulk($container->get('event_command_bus'), $container->get('organizer_owner.repository')));
$consoleApp->add(new UpdateUniqueLabels($container->get('dbal_connection')));
$consoleApp->add(new UpdateUniqueOrganizers($container->get('dbal_connection'), new WebsiteNormalizer()));
$consoleApp->add(new RemoveFacilitiesFromPlace($container->get('event_command_bus'), $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_PLACES)));
$consoleApp->add(new RemoveLabelOffer($container->get('dbal_connection'), $container->get('event_command_bus')));
$consoleApp->add(new RemoveLabelOrganizer($container->get('dbal_connection'), $container->get('event_command_bus')));

$consoleApp->add(new ImportOfferAutoClassificationLabels($container->get('dbal_connection'), $container->get('event_command_bus')));

$consoleApp->add(new ReplaceNewsArticlePublisher($container->get('dbal_connection')));

try {
    $consoleApp->run();
} catch (\Exception $exception) {
    $container->get(ErrorLogger::class)->log($exception);
    $consoleApp->renderException($exception, new ConsoleOutput());
    // Exit with a non-zero status code so a script executing this command gets feedback on whether it was successful or
    // not. This is also how Symfony Console normally does it when it catches exceptions. (Which we disabled)
    exit(1);
} catch (\Error $error) {
    $container->get(ErrorLogger::class)->log($error);
    // The version of Symfony Console that we are on does not support rendering of Errors yet, so after logging it we
    // should re-throw it so PHP itself prints a message and then exits with a non-zero status code.
    throw $error;
}
