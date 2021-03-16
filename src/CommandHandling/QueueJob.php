<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Psr\Log\LoggerInterface;
use Resque_Job;
use Throwable;

class QueueJob
{
    /**
     * @var Resque_Job
     */
    public $job;

    /**
     * @var array
     */
    public $args;

    /**
     * @var ResqueCommandBus
     */
    private static $commandBus;

    /**
     * @var LoggerInterface
     */
    private static $logger;

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
            self::$commandBus->deferredDispatch($command, $context);
        } catch (Throwable $e) {
            self::$logger->error('job_failed', ['exception' => $e]);
        }
    }
}
