<?php

namespace CultuurNet\UDB3\Role\ReadModel\Search;

use Broadway\EventHandling\EventListener;
use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class Projector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var SapiVersion
     */
    private $sapiVersion;


    public function __construct(
        RepositoryInterface $repository,
        SapiVersion $sapiVersion
    ) {
        $this->sapiVersion = $sapiVersion;
        $this->repository = $repository;
    }


    public function applyRoleCreated(
        RoleCreated $roleCreated,
        DomainMessage $domainMessage
    ) {
        $this->repository->save(
            $roleCreated->getUuid()->toNative(),
            $roleCreated->getName()->toNative()
        );
    }


    public function applyRoleRenamed(
        RoleRenamed $roleRenamed,
        DomainMessage $domainMessage
    ) {
        $this->repository->updateName(
            $roleRenamed->getUuid()->toNative(),
            $roleRenamed->getName()->toNative()
        );
    }


    public function applyRoleDeleted(
        RoleDeleted $roleDeleted,
        DomainMessage $domainMessage
    ) {
        $this->repository->remove($roleDeleted->getUuid()->toNative());
    }


    protected function applyConstraintAdded(ConstraintAdded $constraintAdded)
    {
        if ($constraintAdded->getSapiVersion()->sameValueAs($this->sapiVersion)) {
            $this->repository->updateConstraint(
                $constraintAdded->getUuid(),
                $constraintAdded->getQuery()
            );
        }
    }


    protected function applyConstraintUpdated(ConstraintUpdated $constraintUpdated)
    {
        if ($constraintUpdated->getSapiVersion()->sameValueAs($this->sapiVersion)) {
            $this->repository->updateConstraint(
                $constraintUpdated->getUuid(),
                $constraintUpdated->getQuery()
            );
        }
    }


    protected function applyConstraintRemoved(ConstraintRemoved $constraintRemoved)
    {
        if ($constraintRemoved->getSapiVersion()->sameValueAs($this->sapiVersion)) {
            $this->repository->updateConstraint(
                $constraintRemoved->getUuid()
            );
        }
    }
}
