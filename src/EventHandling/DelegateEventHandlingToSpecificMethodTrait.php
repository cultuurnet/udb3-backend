<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventHandling;

use Broadway\Domain\DomainMessage;

trait DelegateEventHandlingToSpecificMethodTrait
{
    /**
     * {@inheritDoc}
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event  = $domainMessage->getPayload();
        $method = $this->getHandleMethodName($event);

        if ($method) {
            $this->$method($event, $domainMessage);
        }
    }

    private function getHandleMethodName($event)
    {
        $classParts = explode('\\', get_class($event));
        $methodName = 'apply' . end($classParts);

        if (!method_exists($this, $methodName)) {
            return null;
        }

        try {
            $parameter = new \ReflectionParameter(array($this, $methodName), 0);
        } catch (\ReflectionException $e) {
            // No parameter for the method, so we ignore it.
            return null;
        }

        $expectedClass = $parameter->getClass();

        if ($expectedClass->getName() !== get_class($event)) {
            return null;
        }

        return $methodName;
    }
}
