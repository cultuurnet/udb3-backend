<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;

class RoleUsersProjector extends RoleProjector
{
    /**
     * @var UserIdentityResolver
     */
    private $userIdentityResolver;

    public function __construct(
        DocumentRepository $repository,
        UserIdentityResolver $userIdentityResolver
    ) {
        parent::__construct($repository);

        $this->userIdentityResolver = $userIdentityResolver;
    }


    public function applyUserAdded(UserAdded $userAdded)
    {
        $document = $this->getDocument($userAdded->getUuid());

        if ($document) {
            $userIdentityDetail = $this->userIdentityResolver->getUserById(
                $userAdded->getUserId()
            );

            if ($userIdentityDetail) {
                $userIdentityDetails = $this->getUserIdentityDetails($document);

                $userKey = $userAdded->getUserId()->toNative();
                $userIdentityDetails[$userKey] = $userIdentityDetail;

                $document = $document->withAssocBody($userIdentityDetails);

                $this->repository->save($document);
            }
        }
    }


    public function applyUserRemoved(UserRemoved $userRemoved)
    {
        $document = $this->getDocument($userRemoved->getUuid());

        if ($document) {
            $userIdentityDetails = $this->getUserIdentityDetails($document);
            unset($userIdentityDetails[$userRemoved->getUserId()->toNative()]);

            $document = $document->withAssocBody($userIdentityDetails);
            $this->repository->save($document);
        }
    }


    public function applyRoleCreated(RoleCreated $roleCreated)
    {
        $this->repository->save(
            new JsonDocument(
                $roleCreated->getUuid()->toString(),
                json_encode([])
            )
        );
    }


    public function applyRoleDeleted(RoleDeleted $roleDeleted)
    {
        $this->repository->remove($roleDeleted->getUuid()->toString());
    }

    private function getDocument(UUID $uuid): ?JsonDocument
    {
        try {
            return $this->repository->fetch($uuid->toString());
        } catch (DocumentDoesNotExist $e) {
            return null;
        }
    }

    /**
     * @return UserIdentityDetails[]
     */
    private function getUserIdentityDetails(JsonDocument $document)
    {
        return json_decode($document->getRawBody(), true);
    }
}
