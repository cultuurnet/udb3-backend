<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;

class UserRolesProjector extends RoleProjector
{
    use DelegateEventHandlingToSpecificMethodTrait;

    private DocumentRepository $roleDetailsDocumentRepository;

    private DocumentRepository $roleUsersDocumentRepository;

    public function __construct(
        DocumentRepository $userRolesDocumentRepository,
        DocumentRepository $roleDetailsDocumentRepository,
        DocumentRepository $roleUsersDocumentRepository
    ) {
        parent::__construct($userRolesDocumentRepository);
        $this->roleDetailsDocumentRepository = $roleDetailsDocumentRepository;
        $this->roleUsersDocumentRepository = $roleUsersDocumentRepository;
    }

    public function applyUserAdded(UserAdded $userAdded): void
    {
        $userId = $userAdded->getUserId();
        $roleId = $userAdded->getUuid()->toString();

        try {
            $roleDetailsDocument = $this->roleDetailsDocumentRepository->fetch($roleId);
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        $roleDetails = $roleDetailsDocument->getBody();

        try {
            $document = $this->repository->fetch($userId);
        } catch (DocumentDoesNotExist $e) {
            $document = new JsonDocument(
                $userId,
                Json::encode([])
            );
        }

        $roles = json_decode($document->getRawBody(), true);
        $roles[$roleId] = $roleDetails;

        $document = $document->withAssocBody($roles);

        $this->repository->save($document);
    }

    public function applyUserRemoved(UserRemoved $userRemoved): void
    {
        $userId = $userRemoved->getUserId();
        $roleId = $userRemoved->getUuid()->toString();

        try {
            $document = $this->repository->fetch($userId);
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        $roles = json_decode($document->getRawBody(), true);
        unset($roles[$roleId]);

        $document = $document->withAssocBody($roles);

        $this->repository->save($document);
    }

    public function applyRoleDetailsProjectedToJSONLD(RoleDetailsProjectedToJSONLD $roleDetailsProjectedToJSONLD): void
    {
        $roleId = $roleDetailsProjectedToJSONLD->getUuid()->toString();

        try {
            $roleDetailsDocument = $this->roleDetailsDocumentRepository->fetch($roleId);
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        try {
            $roleUsersDocument = $this->roleUsersDocumentRepository->fetch($roleId);
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        $roleDetails = $roleDetailsDocument->getBody();

        $roleUsers = json_decode($roleUsersDocument->getRawBody(), true);
        $roleUserIds = array_keys($roleUsers);

        foreach ($roleUserIds as $roleUserId) {
            $userRolesDocument = $this->repository->fetch($roleUserId);

            $userRoles = json_decode($userRolesDocument->getRawBody(), true);
            $userRoles[$roleId] = $roleDetails;

            $userRolesDocument = $userRolesDocument->withAssocBody($userRoles);
            $this->repository->save($userRolesDocument);
        }
    }
}
