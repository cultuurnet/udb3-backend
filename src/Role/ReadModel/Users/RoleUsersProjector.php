<?php

namespace CultuurNet\UDB3\Role\ReadModel\Users;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolverInterface;
use ValueObjects\Identity\UUID;

class RoleUsersProjector extends RoleProjector
{
    /**
     * @var UserIdentityResolverInterface
     */
    private $userIdentityResolver;

    public function __construct(
        DocumentRepositoryInterface $repository,
        UserIdentityResolverInterface $userIdentityResolver
    ) {
        parent::__construct($repository);

        $this->userIdentityResolver = $userIdentityResolver;
    }

    /**
     * @param UserAdded $userAdded
     */
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

                $document = $document->withBody($userIdentityDetails);

                $this->repository->save($document);
            }
        }
    }

    /**
     * @param UserRemoved $userRemoved
     */
    public function applyUserRemoved(UserRemoved $userRemoved)
    {
        $document = $this->getDocument($userRemoved->getUuid());

        if ($document) {
            $userIdentityDetails = $this->getUserIdentityDetails($document);
            unset($userIdentityDetails[$userRemoved->getUserId()->toNative()]);

            $document = $document->withBody($userIdentityDetails);
            $this->repository->save($document);
        }
    }

    /**
     * @param RoleCreated $roleCreated
     */
    public function applyRoleCreated(RoleCreated $roleCreated)
    {
        $this->repository->save(
            new JsonDocument(
                $roleCreated->getUuid()->toNative(),
                json_encode([])
            )
        );
    }

    /**
     * @param RoleDeleted $roleDeleted
     */
    public function applyRoleDeleted(RoleDeleted $roleDeleted)
    {
        $this->repository->remove($roleDeleted->getUuid());
    }

    /**
     * @param UUID $uuid
     * @return JsonDocument|null
     */
    private function getDocument(UUID $uuid)
    {
        $document = null;

        try {
            $document = $this->repository->get($uuid->toNative());
        } catch (DocumentGoneException $e) {
        }

        return $document;
    }

    /**
     * @param JsonDocument $document
     * @return UserIdentityDetails[]
     */
    private function getUserIdentityDetails(JsonDocument $document)
    {
        return json_decode($document->getRawBody(), true);
    }
}
