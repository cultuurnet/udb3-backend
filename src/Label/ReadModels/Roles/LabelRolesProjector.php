<?php

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;

class LabelRolesProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var LabelRolesWriteRepositoryInterface
     */
    private $labelRolesWriteRepository;

    /**
     * LabelRolesProjector constructor.
     */
    public function __construct(
        LabelRolesWriteRepositoryInterface $labelRolesWriteRepository
    ) {
        $this->labelRolesWriteRepository = $labelRolesWriteRepository;
    }


    protected function applyLabelAdded(LabelAdded $labelAdded)
    {
        $this->labelRolesWriteRepository->insertLabelRole(
            $labelAdded->getLabelId(),
            $labelAdded->getUuid()
        );
    }


    protected function applyLabelRemoved(LabelRemoved $labelRemoved)
    {
        $this->labelRolesWriteRepository->removeLabelRole(
            $labelRemoved->getLabelId(),
            $labelRemoved->getUuid()
        );
    }


    protected function applyRoleDeleted(RoleDeleted $roleDeleted)
    {
        $this->labelRolesWriteRepository->removeRole($roleDeleted->getUuid());
    }
}
