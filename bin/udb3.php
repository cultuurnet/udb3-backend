#!/usr/bin/env php
<?php
/**
 * @file
 */

/** @var \Silex\Application $app */
$app = require __DIR__ . '/../bootstrap.php';

$app->register(
    new \Knp\Provider\ConsoleServiceProvider(),
    array(
        'console.name' => 'UDB3',
        'console.version' => '0.0.1',
        'console.project_directory' => __DIR__ . "/..",
    )
);

$console = $app['console'];

$console->run();
