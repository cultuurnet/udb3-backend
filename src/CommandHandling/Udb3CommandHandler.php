<?php

/**
 * @file
 * Contains CultuurNet\UDB3\Udb3CommandHandler.
 */

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandHandler;

/**
 * Abstract Command handler for Udb3.
 */
abstract class Udb3CommandHandler extends CommandHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle($command)
    {

        $method = $this->getHandleMethod($command);

        if (! method_exists($this, $method)) {
            return;
        }

        $parameter = new \ReflectionParameter(array($this, $method), 0);
        $expectedClass = $parameter->getClass();

        if ($expectedClass->getName() == get_class($command)) {
            $this->$method($command);
        }

    }

    private function getHandleMethod($command)
    {
        $classParts = explode('\\', get_class($command));

        return 'handle' . end($classParts);
    }
}
