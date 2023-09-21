<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventHandling;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsProjector;

trait DelegateEventHandlingToSpecificMethodTrait
{
    /**
     * @uses  Projector::applyEventImportedFromUDB2()
     * @uses  Projector::applyEventCreated()
     * @uses  Projector::applyEventCopied()
     * @uses  Projector::applyOwnerChanged()
     * @uses  EventRelationsProjector::applyEventImportedFromUDB2
     * @uses  EventRelationsProjector::applyEventUpdatedFromUDB2
     * @uses  EventRelationsProjector::applyEventCreated
     * @uses  EventRelationsProjector::applyEventCopied
     * @uses  EventRelationsProjector::applyMajorInfoUpdated
     * @uses  EventRelationsProjector::applyLocationUpdated
     * @uses  EventRelationsProjector::applyEventDeleted
     * @uses  EventRelationsProjector::applyOrganizerUpdated
     * @uses  EventRelationsProjector::applyOrganizerDeleted
     * @uses  LabelRolesProjector::applyLabelAdded
     * @uses  LabelRolesProjector::applyLabelRemoved
     * @uses  LabelRolesProjector::applyRoleDeleted
     */
    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();
        $method = $this->getHandleMethodName($event);

        if ($method) {
            $this->$method($domainMessage->getPayload(), $domainMessage);
        }
    }

    // @phpstan-ignore-next-line
    private function getHandleMethodName($event): ?string
    {
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
