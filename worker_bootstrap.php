<?php

use CultuurNet\UDB3\Log\ContextEnrichingLogger;

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

        $app['logger.fatal_job_error'] = new ContextEnrichingLogger(
            $app['logger.command_bus'],
            array('job_id' => $job->payload['id'])
        );

        $errorLoggingShutdownHandler = function () use ($app) {
            $error = error_get_last();

            if ($error["type"] == E_ERROR) {
                $app['logger.fatal_job_error']->error('job_failed');
            }
        };

        // Allows to access the command bus in perform() of jobs that
        // come out of the queue.
        \CultuurNet\UDB3\CommandHandling\QueueJob::setCommandBus(
            $app['event_command_bus_out']
        );

        register_shutdown_function($errorLoggingShutdownHandler);
    }
);
