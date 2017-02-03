#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
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

$consoleApp->run();
