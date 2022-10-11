#!/usr/bin/env php
<?php

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Console\ConsoleServiceProvider;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Error\CliErrorHandlerProvider;
use CultuurNet\UDB3\Error\ErrorLogger;
use League\Container\DefinitionContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

// Set the API_NAME either to "UITPAS_LISTENER" if the "amqp-listen-uitpas" command is run, or the generic ApiName "CLI"
// otherwise.
define('API_NAME', isset($argv[1]) && $argv[1] === 'amqp-listen-uitpas' ? ApiName::UITPAS_LISTENER : ApiName::CLI);

/** @var DefinitionContainerInterface $container */
$container = require __DIR__ . '/../bootstrap.php';

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

$consoleApp->setCommandLoader($container->get(CommandLoaderInterface::class));

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
