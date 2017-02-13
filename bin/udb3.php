#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
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

// This shouldn't be needed in theory, but some process managers have a
// dependency on the command bus, which in turn has a dependency on the
// current user to authorize commands.
// These process managers SHOULD NOT react on replay events, but in the
// current setup they ARE bootstrapped (they just ignore replay events).
/** @var Impersonator $impersonator */
$impersonator = $app['impersonator'];
$impersonator->impersonate($app['udb3_system_user_metadata']);

$consoleApp->add(
    (new ConsumeCommand('amqp-listen', 'amqp.udb2_event_bus_forwarding_consumer'))->withHeartBeat('dbal_connection:keepalive')
);

$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\InstallCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ReplayCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\UpdateCdbXMLCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\SearchCacheWarmCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\SearchCacheClearCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\EventCdbXmlCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\PurgeModelCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ConcludeCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ConcludeByCdbidCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\GeocodeCommand());

$consoleApp->run();
