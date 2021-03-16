<?php

use CultuurNet\UDB3\CommandHandling\QueueJob;
use CultuurNet\UDB3\Log\ContextEnrichingLogger;

require_once 'vendor/autoload.php';

Resque_Event::listen(
    'beforePerform',
    function (Resque_Job $job) {
        /** @var \Silex\Application $app */
        $app = require __DIR__ . '/bootstrap.php';
        $app->boot();

        $logger = new ContextEnrichingLogger($app['logger.command_bus'], ['job_id' => $job->payload['id']]);
        $logger->info('job_started');

        try {
            $args = $job->getArguments();

            $context = unserialize(base64_decode($args['context']));
            $app['impersonator']->impersonate($context);

            // Command bus service name is based on queue name + _command_bus_out.
            // Eg. Queue "event" => command bus "event_command_bus_out".
            $commandBusServiceName = getenv('QUEUE') . '_command_bus_out';

            // Allows to access the command bus and logger in perform() of jobs that come out of the queue.
            QueueJob::setLogger($logger);
            QueueJob::setCommandBus($app[$commandBusServiceName]);
        } catch (Throwable $e) {
            $logger->error('job_failed', ['exception' => $e]);

            // Make sure to exit so the job doesn't get performed if there's an error in the beforePerform
            // Don't re-throw because it will pollute the logs
            exit;
        }
    }
);

Resque_Event::listen(
    'afterPerform',
    function (Resque_Job $job) {
        /** @var \Silex\Application $app */
        $app = require __DIR__ . '/bootstrap.php';
        $app->boot();

        $logger = new ContextEnrichingLogger($app['logger.command_bus'], ['job_id' => $job->payload['id']]);
        $logger->info('job_finished');
    }
);
