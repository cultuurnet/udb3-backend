<?php

require_once 'vendor/autoload.php';

Resque_Event::listen(
    'beforePerform',
    function (Resque_Job $job) {
        /** @var \Silex\Application $app */
        $app = require __DIR__ . '/bootstrap.php';

        $app->boot();

        $args = $job->getArguments();

        $context = unserialize(base64_decode($args['context']));
        $app['impersonator']->impersonate($context);

        // Allows to access the command bus in perform() of jobs that
        // come out of the queue.
        \CultuurNet\UDB3\CommandHandling\QueueJob::setCommandBus(
            $app['event_command_bus_out']
        );
    }
);
