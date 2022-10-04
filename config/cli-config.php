<?php

/** @var HybridContainerApplication $application */
$application = require __DIR__ . '/../bootstrap.php';
$container = $application->getLeagueContainer();

use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\DBAL\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Helper\QuestionHelper;

$connection = $container->get('dbal_connection');

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
