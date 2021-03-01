<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Detail;

use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;

class Projector extends RoleProjector
{
    protected function applyRoleCreated(RoleCreated $roleCreated)
    {
        $this->saveNewDocument(
            $roleCreated->getUuid()->toNative(),
            function (\stdClass $json) use ($roleCreated) {
                $json->uuid = $roleCreated->getUuid()->toNative();
                $json->name = $roleCreated->getName()->toNative();
                $json->permissions = [];
                return $json;
            }
        );
    }


    protected function applyRoleRenamed(RoleRenamed $roleRenamed)
    {
        $document = $this->loadDocumentFromRepositoryByUuid(
            $roleRenamed->getUuid()->toNative()
        );

        $json = $document->getBody();
        $json->name = $roleRenamed->getName()->toNative();

        $this->repository->save($document->withBody($json));
    }


    protected function applyRoleDeleted(RoleDeleted $roleDeleted)
    {
        $this->repository->remove($roleDeleted->getUuid());
    }


    protected function applyConstraintAdded(
        ConstraintAdded $constraintAdded
    ) {
        $document = $this->loadDocumentFromRepositoryByUuid(
            $constraintAdded->getUuid()->toNative()
        );

        $json = $document->getBody();

        if (empty($json->constraints)) {
            $json->constraints = new \stdClass();
        }
        $json->constraints->{$constraintAdded->getSapiVersion()->toNative()} = $constraintAdded->getQuery()->toNative();

        $this->repository->save($document->withBody($json));
    }


    protected function applyConstraintUpdated(
        ConstraintUpdated $constraintUpdated
    ) {
        $document = $this->loadDocumentFromRepositoryByUuid(
            $constraintUpdated->getUuid()->toNative()
        );

        $json = $document->getBody();
        $json->constraints->{$constraintUpdated->getSapiVersion()->toNative()} = $constraintUpdated->getQuery()->toNative();

        $this->repository->save($document->withBody($json));
    }


    protected function applyConstraintRemoved(
        ConstraintRemoved $constraintRemoved
    ) {
        $document = $this->loadDocumentFromRepositoryByUuid(
            $constraintRemoved->getUuid()->toNative()
        );

        $json = $document->getBody();
        $json->constraints->{$constraintRemoved->getSapiVersion()->toNative()} = null;

        $this->repository->save($document->withBody($json));
    }


    public function applyPermissionAdded(PermissionAdded $permissionAdded)
    {
        $document = $this->loadDocumentFromRepositoryByUuid(
            $permissionAdded->getUuid()->toNative()
        );

        $permission = $permissionAdded->getPermission();

        $json = $document->getBody();

        $permissions = property_exists($json, 'permissions') ? $json->permissions : [];
        array_push($permissions, $permission->getName());

        $json->permissions = array_unique($permissions);

        $this->repository->save($document->withBody($json));
    }


    public function applyPermissionRemoved(PermissionRemoved $permissionRemoved)
    {
        $document = $this->loadDocumentFromRepositoryByUuid(
            $permissionRemoved->getUuid()->toNative()
        );

        $permission = $permissionRemoved->getPermission();
        $permissionName = $permission->getName();

        $json = $document->getBody();
        $json->permissions = array_values(
            array_filter(
                $json->permissions,
                function ($item) use ($permissionName) {
                    return $item !== $permissionName;
                }
            )
        );

        $this->repository->save($document->withBody($json));
    }
}
