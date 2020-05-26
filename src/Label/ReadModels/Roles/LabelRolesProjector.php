<?php

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;

class LabelRolesProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var LabelRolesWriteRepositoryInterface
     */
    private $labelRolesWriteRepository;

    /**
     * LabelRolesProjector constructor.
     * @param LabelRolesWriteRepositoryInterface $labelRolesWriteRepository
     */
    public function __construct(
        LabelRolesWriteRepositoryInterface $labelRolesWriteRepository
    ) {
        $this->labelRolesWriteRepository = $labelRolesWriteRepository;
    }

    /**
     * @param LabelAdded $labelAdded
     */
    protected function applyLabelAdded(LabelAdded $labelAdded)
    {
        $this->labelRolesWriteRepository->insertLabelRole(
            $labelAdded->getLabelId(),
            $labelAdded->getUuid()
        );
    }

    /**
     * @param LabelRemoved $labelRemoved
     */
    protected function applyLabelRemoved(LabelRemoved $labelRemoved)
    {
        $this->labelRolesWriteRepository->removeLabelRole(
            $labelRemoved->getLabelId(),
            $labelRemoved->getUuid()
        );
    }

    /**
     * @param RoleDeleted $roleDeleted
     */
    protected function applyRoleDeleted(RoleDeleted $roleDeleted)
    {
        $this->labelRolesWriteRepository->removeRole($roleDeleted->getUuid());
    }
}
