<?php

use CultuurNet\UDB3\CommandHandling\QueueJob;
use CultuurNet\UDB3\Log\ContextEnrichingLogger;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;

require_once 'vendor/autoload.php';

Resque_Event::listen(
    'beforePerform',
    function (Resque_Job $job) {
        /** @var HybridContainerApplication $app */
        $app = require __DIR__ . '/bootstrap.php';
        $container = $app->getLeagueContainer();

        $logger = new ContextEnrichingLogger(
            $container->get('logger_factory.resque_worker')($job->queue),
            ['job_id' => $job->payload['id']]
        );
        $logger->info('job_started');

        try {
            $args = $job->getArguments();

            $context = unserialize(base64_decode($args['context']));
            $container->get('impersonator')->impersonate($context);

            // Command bus service name is based on queue name + _command_bus_out.
            // Eg. Queue "event" => command bus "event_command_bus_out".
            $commandBusServiceName = $job->queue . '_command_bus_out';

            // Allows to access the command bus and logger in perform() of jobs that come out of the queue.
            QueueJob::setLogger($logger);
            QueueJob::setCommandBus($container->get($commandBusServiceName));
        } catch (Throwable $e) {
            $logger->error('job_failed', ['exception' => $e]);
            $logger->info('job_finished');

            // Make sure to exit so the job doesn't get performed if there's an error in the beforePerform
            // Don't re-throw because it will pollute the logs
            exit;
        }
    }
);

Resque_Event::listen(
    'afterPerform',
    function (Resque_Job $job) {
        /** @var HybridContainerApplication $app */
        $app = require __DIR__ . '/bootstrap.php';
        $container = $app->getLeagueContainer();

        $logger = new ContextEnrichingLogger(
            $container->get('logger_factory.resque_worker')($job->queue),
            ['job_id' => $job->payload['id']]
        );
        $logger->info('job_finished');
    }
);
