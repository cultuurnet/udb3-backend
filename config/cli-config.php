<?php
/**
 * @file
 */

$application = require __DIR__ . '/../bootstrap.php';

use Doctrine\DBAL\Tools\Console\ConsoleRunner;

$connection = $application['dbal_connection'];

$commands = [
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),
];

$helperSet = ConsoleRunner::createHelperSet($connection);
$helperSet->set(
    new \Symfony\Component\Console\Helper\DialogHelper(),
    'dialog'
);

return $helperSet;
