<?php

namespace CultuurNet\UDB3\CommandHandling;

use Resque_Job;

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

    public static function setCommandBus(ResqueCommandBus $commandBus): void
    {
        self::$commandBus = $commandBus;
    }

    public function perform(): void
    {
        $command = unserialize(base64_decode($this->args['command']));
        $context = unserialize(base64_decode($this->args['context']));
        self::$commandBus->setContext($context);
        self::$commandBus->deferredDispatch($this->job->payload['id'], $command);
    }
}
