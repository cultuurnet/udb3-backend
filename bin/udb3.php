#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use CultuurNet\UDB3\Silex\JwtImpersonator;
use Knp\Provider\ConsoleServiceProvider;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use ValueObjects\String\String as StringLiteral;

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

$dispatcher = new EventDispatcher();
$dispatcher->addListener(
    ConsoleEvents::COMMAND,
    function (ConsoleCommandEvent $event) use ($app) {
        if ($event->getCommand() instanceof ConsumeCommand) {
            $jwtImpersonator = new JwtImpersonator($app);

            $jwtImpersonator->updateImpersonator(
                $app['impersonator'],
                new StringLiteral($app['config']['amqp']['jwt'])
            );
        }
    }
);
$consoleApp->setDispatcher($dispatcher);

$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\InstallCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ReplayCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\UpdateCdbXMLCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\SearchCacheWarmCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\SearchCacheClearCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\EventCdbXmlCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\PurgeModelCommand());
$consoleApp->add(new \CultuurNet\UDB3\Silex\Console\ElasticsearchCommand());

$consoleApp->run();
