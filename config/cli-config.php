<?php
/**
 * @file
 */

$application = require __DIR__ . '/../bootstrap.php';

use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\DBAL\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Helper\QuestionHelper;

$connection = $application['dbal_connection'];

$commands = [
    new ExecuteCommand(),
    new GenerateCommand(),
    new MigrateCommand(),
    new StatusCommand(),
    new VersionCommand(),
];

$helperSet = ConsoleRunner::createHelperSet($connection);
$helperSet->set(
    new QuestionHelper(),
    'question'
);

return $helperSet;
