<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Psr\Log\LoggerInterface;
use Resque_Job;
use Throwable;

class QueueJob
{
    public Resque_Job $job;

    public array $args;

    private static ResqueCommandBus $commandBus;

    private static LoggerInterface $logger;

    public static function setCommandBus(ResqueCommandBus $commandBus): void
    {
        self::$commandBus = $commandBus;
    }

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public function perform(): void
    {
        try {
            $command = unserialize(base64_decode($this->args['command']));
            $context = unserialize(base64_decode($this->args['context']));
            self::$commandBus->setLogger(self::$logger);
            self::$commandBus->setContext($context);
            self::$commandBus->deferredDispatch($command);
        } catch (Throwable $e) {
            self::$logger->error('job_failed', ['exception' => $e]);
        }

        // Make sure to revert the context, even if there was an Error/Exception.
        self::$commandBus->setContext(null);
    }
}
