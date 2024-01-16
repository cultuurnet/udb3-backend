<?php

use Doctrine\DBAL\Tools\Console\ConsoleRunner;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\QuestionHelper;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../bootstrap.php';
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
