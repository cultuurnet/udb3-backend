#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use CultuurNet\UDB3\Silex\Console\ConcludeByCdbidCommand;
use CultuurNet\UDB3\Silex\Console\ConcludeCommand;
use CultuurNet\UDB3\Silex\Console\EventAncestorsCommand;
use CultuurNet\UDB3\Silex\Console\EventCdbXmlCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Silex\Console\GeocodeEventCommand;
use CultuurNet\UDB3\Silex\Console\GeocodePlaceCommand;
use CultuurNet\UDB3\Silex\Console\ImportEventCdbXmlCommand;
use CultuurNet\UDB3\Silex\Console\ImportRoleConstraintsCommand;
use CultuurNet\UDB3\Silex\Console\ImportSavedSearchesCommand;
use CultuurNet\UDB3\Silex\Console\InstallCommand;
use CultuurNet\UDB3\Silex\Console\PermissionCommand;
use CultuurNet\UDB3\Silex\Console\PurgeModelCommand;
use CultuurNet\UDB3\Silex\Console\ReplayCommand;
use CultuurNet\UDB3\Silex\Console\SearchCacheClearCommand;
use CultuurNet\UDB3\Silex\Console\SearchCacheWarmCommand;
use CultuurNet\UDB3\Silex\Console\UpdateCdbXMLCommand;
use CultuurNet\UDB3\Silex\Console\ValidatePlaceJsonLdCommand;
use CultuurNet\UDB3\Silex\Impersonator;
use Knp\Provider\ConsoleServiceProvider;

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

// An udb3 system user is needed for conclude and geocode commands.
// Because of the changes for geocoding the amqp forwarding for udb2 imports also needs a user.
// To avoid fixing this locally in the amqp-silex lib, all CLI commands are executed as udb3 system user.
/** @var Impersonator $impersonator */
$impersonator = $app['impersonator'];
$impersonator->impersonate($app['udb3_system_user_metadata']);

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

$consoleApp->add(new InstallCommand());
$consoleApp->add(new ReplayCommand());
$consoleApp->add(new EventAncestorsCommand());
$consoleApp->add(new UpdateCdbXMLCommand());
$consoleApp->add(new SearchCacheWarmCommand());
$consoleApp->add(new SearchCacheClearCommand());
$consoleApp->add(new EventCdbXmlCommand());
$consoleApp->add(new PurgeModelCommand());
$consoleApp->add(new ConcludeCommand());
$consoleApp->add(new ConcludeByCdbidCommand());
$consoleApp->add(new GeocodePlaceCommand());
$consoleApp->add(new GeocodeEventCommand());
$consoleApp->add(new PermissionCommand());
$consoleApp->add(new FireProjectedToJSONLDForRelationsCommand());
$consoleApp->add(new FireProjectedToJSONLDCommand());
$consoleApp->add(new ImportSavedSearchesCommand());
$consoleApp->add(new ImportRoleConstraintsCommand());
$consoleApp->add(new ImportEventCdbXmlCommand());
$consoleApp->add(new ValidatePlaceJsonLdCommand());

$consoleApp->run();
