#!/usr/bin/env php
<?php
/**
 * @file
 */

use Knp\Provider\ConsoleServiceProvider;

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

$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\AMQPListenCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\InstallCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ReplayCommand());

$consoleApp->run();
