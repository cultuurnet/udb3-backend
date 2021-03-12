#!/usr/bin/env php
<?php

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\LocationMarkedAsDuplicateProcessManager;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\ConfigWriter;
use CultuurNet\UDB3\Silex\Console\ChangeOfferOwner;
use CultuurNet\UDB3\Silex\Console\ChangeOfferOwnerInBulk;
use CultuurNet\UDB3\Silex\Console\ConcludeByCdbidCommand;
use CultuurNet\UDB3\Silex\Console\ConcludeCommand;
use CultuurNet\UDB3\Silex\Console\ConsumeCommand;
use CultuurNet\UDB3\Silex\Console\DispatchMarkedAsDuplicateEventCommand;
use CultuurNet\UDB3\Silex\Console\EventAncestorsCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Silex\Console\GeocodeEventCommand;
use CultuurNet\UDB3\Silex\Console\GeocodePlaceCommand;
use CultuurNet\UDB3\Silex\Console\ImportOfferAutoClassificationLabels;
use CultuurNet\UDB3\Silex\Console\ImportEventCdbXmlCommand;
use CultuurNet\UDB3\Silex\Console\ImportPlaceCdbXmlCommand;
use CultuurNet\UDB3\Silex\Console\MarkPlaceAsDuplicateCommand;
use CultuurNet\UDB3\Silex\Console\PurgeModelCommand;
use CultuurNet\UDB3\Silex\Console\ReindexOffersWithPopularityScore;
use CultuurNet\UDB3\Silex\Console\ReplayCommand;
use CultuurNet\UDB3\Silex\Console\UpdateOfferStatusCommand;
use CultuurNet\UDB3\Silex\Console\UpdateUniqueLabels;
use CultuurNet\UDB3\Silex\Console\UpdateUniqueOrganizers;
use CultuurNet\UDB3\Silex\Console\ValidatePlaceJsonLdCommand;
use CultuurNet\UDB3\Silex\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\PsrLoggerErrorHandler;
use CultuurNet\UDB3\Silex\PurgeServiceProvider;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use CultuurNet\UDB3\Silex\SentryErrorHandler;
use CultuurNet\UDB3\User\UserIdentityDetails;
use Knp\Provider\ConsoleServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var \Silex\Application $app */
$app = require __DIR__ . '/../bootstrap.php';

$app->register(
    new ConsoleServiceProvider(),
    [
        'console.name' => 'UDB3',
        'console.version' => '0.0.1',
        'console.project_directory' => __DIR__ . '/..',
    ]
);

/** @var \Knp\Console\Application $consoleApp */
$consoleApp = $app['console'];
$consoleApp->setCatchExceptions(false);

// An udb3 system user is needed for conclude and geocode commands.
// Because of the changes for geocoding the amqp forwarding for udb2 imports also needs a user.
// To avoid fixing this locally in the amqp-silex lib, all CLI commands are executed as udb3 system user.
$app['impersonator']->impersonate(
    new Metadata(
        [
            'user_id' => UserIdentityDetails::SYSTEM_USER_UUID,
            'user_nick' => 'udb3',
        ]
    )
);

$app['api_name'] = ApiName::CLI;

$consoleApp->add(
    (new ConsumeCommand('amqp-listen', 'amqp.udb2_event_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(
    (new ConsumeCommand('amqp-listen-uitpas', 'amqp.uitpas_event_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(
    (new ConsumeCommand('amqp-listen-imports', 'import_command_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(
    (new ConsumeCommand('amqp-listen-curators', 'curators_event_bus_forwarding_consumer'))
        ->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(new ReplayCommand($app['event_command_bus'], $app['dbal_connection'], $app['eventstore_payload_serializer'], $app['event_bus'], new ConfigWriter($app)));
$consoleApp->add(new EventAncestorsCommand($app['event_command_bus'], $app['event_store']));
$consoleApp->add(new PurgeModelCommand($app[PurgeServiceProvider::PURGE_SERVICE_MANAGER]));
$consoleApp->add(new ConcludeCommand($app['event_command_bus'], $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS]));
$consoleApp->add(new ConcludeByCdbidCommand($app['event_command_bus']));
$consoleApp->add(new GeocodePlaceCommand($app['event_command_bus'], $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_PLACES], $app['place_jsonld_repository']));
$consoleApp->add(new GeocodeEventCommand($app['event_command_bus'], $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS], $app['event_jsonld_repository']));
$consoleApp->add(new FireProjectedToJSONLDForRelationsCommand($app['event_bus'], $app['dbal_connection'], $app[OrganizerJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY], $app[PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY]));
$consoleApp->add(new FireProjectedToJSONLDCommand($app['event_bus'], $app[OrganizerJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY], $app[PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY]));
$consoleApp->add(new ImportEventCdbXmlCommand($app['event_command_bus'], $app['event_bus']));
$consoleApp->add(new ImportPlaceCdbXmlCommand($app['event_command_bus'], $app['event_bus']));
$consoleApp->add(new ValidatePlaceJsonLdCommand($app['event_command_bus']));
$consoleApp->add(new MarkPlaceAsDuplicateCommand($app['event_command_bus'], $app[LocationMarkedAsDuplicateProcessManager::class]));
$consoleApp->add(new DispatchMarkedAsDuplicateEventCommand($app['event_command_bus'], $app[LocationMarkedAsDuplicateProcessManager::class], $app['event_bus']));
$consoleApp->add(
    new ReindexOffersWithPopularityScore(
        OfferType::EVENT(),
        $app['dbal_connection'],
        $app['amqp.publisher'],
        $app[EventJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY]
    )
);
$consoleApp->add(
    new ReindexOffersWithPopularityScore(
        OfferType::PLACE(),
        $app['dbal_connection'],
        $app['amqp.publisher'],
        $app[PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY]
    )
);
$consoleApp->add(new UpdateOfferStatusCommand(OfferType::EVENT(), $app['event_command_bus'], $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS]));
$consoleApp->add(new UpdateOfferStatusCommand(OfferType::PLACE(), $app['event_command_bus'], $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_PLACES]));
$consoleApp->add(new ChangeOfferOwner($app['event_command_bus']));
$consoleApp->add(new ChangeOfferOwnerInBulk($app['event_command_bus'], $app['offer_permission_query']));
$consoleApp->add(new UpdateUniqueLabels($app['dbal_connection']));
$consoleApp->add(new UpdateUniqueOrganizers($app['dbal_connection']));

$consoleApp->add(new ImportOfferAutoClassificationLabels($app['dbal_connection'], $app['event_command_bus']));

try {
    $consoleApp->run();
} catch (\Exception $exception) {
    $app[PsrLoggerErrorHandler::class]->handle($exception);
    $consoleApp->renderException($exception, new ConsoleOutput());
} catch (\Error $error) {
    $app[PsrLoggerErrorHandler::class]->handle($error);
    throw $error;
}
