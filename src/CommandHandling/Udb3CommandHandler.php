<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandHandler;

/**
 * Abstract Command handler for Udb3.
 */
abstract class Udb3CommandHandler implements CommandHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle($command): void
    {
        $method = $this->getHandleMethod($command);

        if (! method_exists($this, $method)) {
            return;
        }

        $parameter = new \ReflectionParameter(array($this, $method), 0);
        $expectedClass = $parameter->getClass();

        if ($expectedClass->getName() === get_class($command)) {
            $this->$method($command);
        }
    }

    private function getHandleMethod($command): string
    {
        $classParts = explode('\\', get_class($command));

        return 'handle' . end($classParts);
    }
}
