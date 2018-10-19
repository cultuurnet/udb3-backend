#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDCommand;
use CultuurNet\UDB3\Silex\Console\FireProjectedToJSONLDForRelationsCommand;
use CultuurNet\UDB3\Silex\Console\Import\ValidatePlaceCommand;
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

$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\InstallCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ReplayCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\EventAncestorsCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\UpdateCdbXMLCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\SearchCacheWarmCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\SearchCacheClearCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\EventCdbXmlCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\PurgeModelCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ConcludeCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ConcludeByCdbidCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\GeocodePlaceCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\GeocodeEventCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\PermissionCommand());
$consoleApp->add(new FireProjectedToJSONLDForRelationsCommand());
$consoleApp->add(new FireProjectedToJSONLDCommand());
$consoleApp->add(new ValidatePlaceCommand());

$consoleApp->run();
