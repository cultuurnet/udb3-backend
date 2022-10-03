#!/usr/bin/env php
<?php

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Console\ConsoleServiceProvider;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Error\CliErrorHandlerProvider;
use CultuurNet\UDB3\Error\ErrorLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

const API_NAME = ApiName::CLI;

/** @var HybridContainerApplication $app */
$app = require __DIR__ . '/../bootstrap.php';
$container = $app->getLeagueContainer();

$container->addServiceProvider(new CliErrorHandlerProvider());
$container->addServiceProvider(new ConsoleServiceProvider());

$consoleApp = new Application('UDB3');
$consoleApp->setCatchExceptions(false);

// An udb3 system user is needed for geocode commands and updating the status of one or multiple offers.
// Because of the changes for geocoding the amqp forwarding for udb2 imports also needs a user.
// To avoid fixing this locally in the amqp-silex lib, all CLI commands are executed as udb3 system user.
$container->get('impersonator')->impersonate(
    new Metadata(
        [
            'user_id' => $container->get('system_user_id'),
        ]
    )
);

$commands = [
    'amqp-listen-uitpas',
    'replay',
    'event:ancestors',
    'purge',
    'place:geocode',
    'event:geocode',
    'organizer:geocode',
    'fire-projected-to-jsonld-for-relations',
    'fire-projected-to-jsonld',
    'place:process-duplicates',
    'event:reindex-offers-with-popularity',
    'place:reindex-offers-with-popularity',
    'event:reindex-events-with-recommendations',
    'event:status:update',
    'place:status:update',
    'event:booking-availability:update',
    'event:attendanceMode:update',
    'offer:change-owner',
    'offer:change-owner-bulk',
    'organizer:change-owner',
    'organizer:change-owner-bulk',
    'label:update-unique',
    'organizer:update-unique',
    'place:facilities:remove',
    'offer:remove-label',
    'organizer:remove-label',
    'offer:import-auto-classification-labels',
    'article:replace-publisher',
];
$commandServices = array_map(fn (string $command) => 'console.' . $command, $commands);
$commandMap = array_combine($commands, $commandServices);
$consoleApp->setCommandLoader(new ContainerCommandLoader($container, $commandMap));

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
