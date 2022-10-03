#!/usr/bin/env php
<?php

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Console\ConsoleServiceProvider;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Error\CliErrorHandlerProvider;
use CultuurNet\UDB3\Error\ErrorLogger;
use Symfony\Component\Console\Application;
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

$consoleApp->add($container->get('console.amqp-listen-uitpas'));
$consoleApp->add($container->get('console.replay'));
$consoleApp->add($container->get('console.event:ancestors'));
$consoleApp->add($container->get('console.purge'));
$consoleApp->add($container->get('console.place:geocode'));
$consoleApp->add($container->get('console.event:geocode'));
$consoleApp->add($container->get('console.organizer:geocode'));
$consoleApp->add($container->get('console.fire-projected-to-jsonld-for-relations'));
$consoleApp->add($container->get('console.fire-projected-to-jsonld'));
$consoleApp->add($container->get('console.place:process-duplicates'));
$consoleApp->add($container->get('console.event:reindex-offers-with-popularity'));
$consoleApp->add($container->get('console.place:reindex-offers-with-popularity'));
$consoleApp->add($container->get('console.event:reindex-events-with-recommendations'));
$consoleApp->add($container->get('console.event:status:update'));
$consoleApp->add($container->get('console.place:status:update'));
$consoleApp->add($container->get('console.event:booking-availability:update'));
$consoleApp->add($container->get('console.event:attendanceMode:update'));
$consoleApp->add($container->get('console.offer:change-owner'));
$consoleApp->add($container->get('console.offer:change-owner-bulk'));
$consoleApp->add($container->get('console.organizer:change-owner'));
$consoleApp->add($container->get('console.organizer:change-owner-bulk'));
$consoleApp->add($container->get('console.label:update-unique'));
$consoleApp->add($container->get('console.organizer:update-unique'));
$consoleApp->add($container->get('console.place:facilities:remove'));
$consoleApp->add($container->get('console.offer:remove-label'));
$consoleApp->add($container->get('console.organizer:remove-label'));
$consoleApp->add($container->get('console.offer:import-auto-classification-labels'));
$consoleApp->add($container->get('console.article:replace-publisher'));

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
