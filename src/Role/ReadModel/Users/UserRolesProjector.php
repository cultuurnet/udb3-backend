<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;

class UserRolesProjector extends RoleProjector
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var DocumentRepository
     */
    private $roleDetailsDocumentRepository;

    /**
     * @var DocumentRepository
     */
    private $roleUsersDocumentRepository;


    public function __construct(
        DocumentRepository $userRolesDocumentRepository,
        DocumentRepository $roleDetailsDocumentRepository,
        DocumentRepository $roleUsersDocumentRepository
    ) {
        parent::__construct($userRolesDocumentRepository);
        $this->roleDetailsDocumentRepository = $roleDetailsDocumentRepository;
        $this->roleUsersDocumentRepository = $roleUsersDocumentRepository;
    }


    public function applyUserAdded(UserAdded $userAdded)
    {
        $userId = $userAdded->getUserId()->toNative();
        $roleId = $userAdded->getUuid()->toNative();

        try {
            $roleDetailsDocument = $this->roleDetailsDocumentRepository->get($roleId);
        } catch (DocumentGoneException $e) {
            return;
        }

        if (empty($roleDetailsDocument)) {
            return;
        }

        $roleDetails = $roleDetailsDocument->getBody();

        $document = $this->repository->get($userId);

        if (empty($document)) {
            $document = new JsonDocument(
                $userId,
                json_encode([])
            );
        }

        $roles = json_decode($document->getRawBody(), true);
        $roles[$roleId] = $roleDetails;

        $document = $document->withAssocBody($roles);

        $this->repository->save($document);
    }


    public function applyUserRemoved(UserRemoved $userRemoved)
    {
        $userId = $userRemoved->getUserId()->toNative();
        $roleId = $userRemoved->getUuid()->toNative();

        try {
            $document = $this->repository->get($userId);
        } catch (DocumentGoneException $e) {
            return;
        }

        if (empty($document)) {
            return;
        }

        $roles = json_decode($document->getRawBody(), true);
        unset($roles[$roleId]);

        $document = $document->withAssocBody($roles);

        $this->repository->save($document);
    }


    public function applyRoleDetailsProjectedToJSONLD(RoleDetailsProjectedToJSONLD $roleDetailsProjectedToJSONLD)
    {
        $roleId = $roleDetailsProjectedToJSONLD->getUuid()->toNative();

        try {
            $roleDetailsDocument = $this->roleDetailsDocumentRepository->get($roleId);
        } catch (DocumentGoneException $e) {
            return;
        }

        if (is_null($roleDetailsDocument)) {
            return;
        }

        try {
            $roleUsersDocument = $this->roleUsersDocumentRepository->get($roleId);
        } catch (DocumentGoneException $e) {
            return;
        }

        if (is_null($roleUsersDocument)) {
            return;
        }

        $roleDetails = $roleDetailsDocument->getBody();

        $roleUsers = json_decode($roleUsersDocument->getRawBody(), true);
        $roleUserIds = array_keys($roleUsers);

        foreach ($roleUserIds as $roleUserId) {
            $userRolesDocument = $this->repository->get($roleUserId);

            if (is_null($userRolesDocument)) {
                continue;
            }

            $userRoles = json_decode($userRolesDocument->getRawBody(), true);
            $userRoles[$roleId] = $roleDetails;

            $userRolesDocument = $userRolesDocument->withAssocBody($userRoles);
            $this->repository->save($userRolesDocument);
        }
    }
}
