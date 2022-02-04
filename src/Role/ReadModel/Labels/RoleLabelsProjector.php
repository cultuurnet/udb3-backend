<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use CultuurNet\UDB3\Label\Events\LabelDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
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
    /**
     * @var ReadRepositoryInterface
     */
    private $labelJsonRepository;

    /**
     * @var DocumentRepository
     */
    private $labelRolesRepository;


    public function __construct(
        DocumentRepository $repository,
        ReadRepositoryInterface $labelJsonRepository,
        DocumentRepository $labelRolesRepository
    ) {
        parent::__construct($repository);

        $this->labelJsonRepository = $labelJsonRepository;
        $this->labelRolesRepository = $labelRolesRepository;
    }


    public function applyLabelAdded(LabelAdded $labelAdded)
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


    public function applyLabelRemoved(LabelRemoved $labelRemoved)
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


    public function applyLabelDetailsProjectedToJSONLD(LabelDetailsProjectedToJSONLD $labelDetailsProjectedToJSONLD)
    {
        $labelId = $labelDetailsProjectedToJSONLD->getUuid()->toString();
        try {
            $document = $this->labelRolesRepository->fetch($labelId);
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        $roles = json_decode($document->getRawBody());

        foreach ($roles as $roleId) {
            $role = $this->getDocument(new UUID($roleId));

            if ($role) {
                $labelDetails = $this->getLabelDetails($role);
                $labelDetails[$labelId] = $this->labelJsonRepository->getByUuid($labelDetailsProjectedToJSONLD->getUuid());
                $role = $role->withAssocBody($labelDetails);
                $this->repository->save($role);
            }
        }
    }


    public function applyRoleCreated(RoleCreated $roleCreated)
    {
        $document = $this->createNewDocument($roleCreated->getUuid());
        $this->repository->save($document);
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
     * @return Entity[]
     */
    private function getLabelDetails(JsonDocument $document)
    {
        return json_decode($document->getRawBody(), true);
    }

    /**
     * @return JsonDocument
     */
    private function createNewDocument(UUID $uuid)
    {
        $document = new JsonDocument(
            $uuid->toString(),
            json_encode([])
        );
        return $document;
    }
}
