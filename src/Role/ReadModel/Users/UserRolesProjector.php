<?php

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;

class UserRolesProjector extends RoleProjector
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var DocumentRepositoryInterface
     */
    private $roleDetailsDocumentRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $roleUsersDocumentRepository;

    /**
     * @param DocumentRepositoryInterface $userRolesDocumentRepository
     * @param DocumentRepositoryInterface $roleDetailsDocumentRepository
     * @param DocumentRepositoryInterface $roleUsersDocumentRepository
     */
    public function __construct(
        DocumentRepositoryInterface $userRolesDocumentRepository,
        DocumentRepositoryInterface $roleDetailsDocumentRepository,
        DocumentRepositoryInterface $roleUsersDocumentRepository
    ) {
        parent::__construct($userRolesDocumentRepository);
        $this->roleDetailsDocumentRepository = $roleDetailsDocumentRepository;
        $this->roleUsersDocumentRepository = $roleUsersDocumentRepository;
    }

    /**
     * @param UserAdded $userAdded
     */
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

        $document = $document->withBody($roles);

        $this->repository->save($document);
    }

    /**
     * @param UserRemoved $userRemoved
     */
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

        $document = $document->withBody($roles);

        $this->repository->save($document);
    }

    /**
     * @param RoleDetailsProjectedToJSONLD $roleDetailsProjectedToJSONLD
     */
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

            $userRolesDocument = $userRolesDocument->withBody($userRoles);
            $this->repository->save($userRolesDocument);
        }
    }
}
