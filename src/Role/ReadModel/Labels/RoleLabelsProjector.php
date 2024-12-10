<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Events\LabelDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;

class RoleLabelsProjector extends RoleProjector
{
    private ReadRepositoryInterface $labelJsonRepository;

    private DocumentRepository $labelRolesRepository;


    public function __construct(
        DocumentRepository $repository,
        ReadRepositoryInterface $labelJsonRepository,
        DocumentRepository $labelRolesRepository
    ) {
        parent::__construct($repository);

        $this->labelJsonRepository = $labelJsonRepository;
        $this->labelRolesRepository = $labelRolesRepository;
    }


    public function applyLabelAdded(LabelAdded $labelAdded): void
    {
        $document = $this->getDocument($labelAdded->getUuid());

        if ($document) {
            $labelDetails = $this->getLabelDetails($document);
            $label = $this->labelJsonRepository->getByUuid($labelAdded->getLabelId());

            if ($label) {
                $labelDetails[$label->getUuid()->toString()] = $label;
                $document = $document->withAssocBody($labelDetails);
                $this->repository->save($document);
            }
        }
    }


    public function applyLabelRemoved(LabelRemoved $labelRemoved): void
    {
        $document = $this->getDocument($labelRemoved->getUuid());

        if ($document) {
            $labelDetails = $this->getLabelDetails($document);
            $label = $this->labelJsonRepository->getByUuid($labelRemoved->getLabelId());

            if ($label) {
                unset($labelDetails[$label->getUuid()->toString()]);
                $document = $document->withAssocBody($labelDetails);
                $this->repository->save($document);
            }
        }
    }


    public function applyLabelDetailsProjectedToJSONLD(LabelDetailsProjectedToJSONLD $labelDetailsProjectedToJSONLD): void
    {
        $labelId = $labelDetailsProjectedToJSONLD->getUuid()->toString();
        try {
            $document = $this->labelRolesRepository->fetch($labelId);
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        $roles = Json::decode($document->getRawBody());

        foreach ($roles as $roleId) {
            $role = $this->getDocument(new Uuid($roleId));

            if ($role) {
                $labelDetails = $this->getLabelDetails($role);
                $labelDetails[$labelId] = $this->labelJsonRepository->getByUuid($labelDetailsProjectedToJSONLD->getUuid());
                $role = $role->withAssocBody($labelDetails);
                $this->repository->save($role);
            }
        }
    }


    public function applyRoleCreated(RoleCreated $roleCreated): void
    {
        $document = $this->createNewDocument($roleCreated->getUuid());
        $this->repository->save($document);
    }


    public function applyRoleDeleted(RoleDeleted $roleDeleted): void
    {
        $this->repository->remove($roleDeleted->getUuid()->toString());
    }

    private function getDocument(Uuid $uuid): ?JsonDocument
    {
        try {
            return $this->repository->fetch($uuid->toString());
        } catch (DocumentDoesNotExist $e) {
            return null;
        }
    }

    /**
     * @return Entity[]
     */
    private function getLabelDetails(JsonDocument $document): array
    {
        return Json::decodeAssociatively($document->getRawBody());
    }

    private function createNewDocument(Uuid $uuid): JsonDocument
    {
        return new JsonDocument(
            $uuid->toString(),
            Json::encode([])
        );
    }
}
