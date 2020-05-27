<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\CommandHandling;

class QueueJob
{
    /**
     * @var \Resque_Job
     */
    public $job;

    /**
     * @var array
     */
    public $args;

    /**
     * @var ResqueCommandBus|ContextAwareInterface
     */
    private static $commandBus;

    public static function setCommandBus(ResqueCommandBus $commandBus)
    {
        self::$commandBus = $commandBus;
    }

    public function perform()
    {
        $command = unserialize(base64_decode($this->args['command']));

        if (self::$commandBus instanceof ContextAwareInterface) {
            $context = unserialize(base64_decode($this->args['context']));
            self::$commandBus->setContext($context);
        }

        self::$commandBus->deferredDispatch($this->job->payload['id'], $command);
    }
}
