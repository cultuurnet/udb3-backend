<?php

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label\Events\LabelDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;
use ValueObjects\Identity\UUID;

class RoleLabelsProjector extends RoleProjector
{
    /**
     * @var ReadRepositoryInterface
     */
    private $labelJsonRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $labelRolesRepository;

    /**
     * @param \CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface $repository
     * @param \CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface $labelJsonRepository
     * @param \CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface $labelRolesRepository
     */
    public function __construct(
        DocumentRepositoryInterface $repository,
        ReadRepositoryInterface $labelJsonRepository,
        DocumentRepositoryInterface $labelRolesRepository
    ) {
        parent::__construct($repository);

        $this->labelJsonRepository = $labelJsonRepository;
        $this->labelRolesRepository = $labelRolesRepository;
    }

    /**
     * @param LabelAdded $labelAdded
     */
    public function applyLabelAdded(LabelAdded $labelAdded)
    {
        $document = $this->getDocument($labelAdded->getUuid());

        if ($document) {
            $labelDetails = $this->getLabelDetails($document);
            $label = $this->labelJsonRepository->getByUuid($labelAdded->getLabelId());

            if ($label) {
                $labelDetails[$label->getUuid()->toNative()] = $label;
                $document = $document->withBody($labelDetails);
                $this->repository->save($document);
            }
        }
    }

    /**
     * @param LabelRemoved $labelRemoved
     */
    public function applyLabelRemoved(LabelRemoved $labelRemoved)
    {
        $document = $this->getDocument($labelRemoved->getUuid());

        if ($document) {
            $labelDetails = $this->getLabelDetails($document);
            $label = $this->labelJsonRepository->getByUuid($labelRemoved->getLabelId());

            if ($label) {
                unset($labelDetails[$label->getUuid()->toNative()]);
                $document = $document->withBody($labelDetails);
                $this->repository->save($document);
            }
        }
    }

    /**
     * @param LabelDetailsProjectedToJSONLD $labelDetailsProjectedToJSONLD
     */
    public function applyLabelDetailsProjectedToJSONLD(LabelDetailsProjectedToJSONLD $labelDetailsProjectedToJSONLD)
    {
        $labelId = $labelDetailsProjectedToJSONLD->getUuid()->toNative();
        $document = $this->labelRolesRepository->get($labelId);

        if ($document) {
            $roles = json_decode($document->getRawBody());

            foreach ($roles as $roleId) {
                $role = $this->getDocument(new UUID($roleId));

                if ($role) {
                    $labelDetails = $this->getLabelDetails($role);
                    $labelDetails[$labelId] = $this->labelJsonRepository->getByUuid($labelDetailsProjectedToJSONLD->getUuid());
                    $role = $role->withBody($labelDetails);
                    $this->repository->save($role);
                }
            }
        }
    }

    /**
     * @param RoleCreated $roleCreated
     */
    public function applyRoleCreated(RoleCreated $roleCreated)
    {
        $document = $this->createNewDocument($roleCreated->getUuid());
        $this->repository->save($document);
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
     * @return Entity[]
     */
    private function getLabelDetails(JsonDocument $document)
    {
        return json_decode($document->getRawBody(), true);
    }

    /**
     * @param UUID $uuid
     * @return JsonDocument
     */
    private function createNewDocument(UUID $uuid)
    {
        $document = new JsonDocument(
            $uuid->toNative(),
            json_encode([])
        );
        return $document;
    }
}
