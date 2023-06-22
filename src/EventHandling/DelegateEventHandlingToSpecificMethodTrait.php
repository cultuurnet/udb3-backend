<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventHandling;

use Broadway\Domain\DomainMessage;

trait DelegateEventHandlingToSpecificMethodTrait
{
    public function handle(DomainMessage $domainMessage): void
    {
        $method = $this->getHandleMethodName($domainMessage);

        if ($method) {
            $this->$method($domainMessage->getPayload(), $domainMessage);
        }
    }

    private function getHandleMethodName(DomainMessage $domainMessage): ?string
    {
        $event = $domainMessage->getPayload();

        $classParts = explode('\\', get_class($event));
        $methodName = 'apply' . end($classParts);

        if (!method_exists($this, $methodName)) {
            return null;
        }

        try {
            $parameter = new \ReflectionParameter([$this, $methodName], 0);
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
