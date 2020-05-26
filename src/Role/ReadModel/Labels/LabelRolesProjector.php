<?php

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Label\Events\Created as LabelCreated;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;
use ValueObjects\Identity\UUID;

class LabelRolesProjector extends RoleProjector
{
    /**
     * @param LabelAdded $labelAdded
     */
    public function applyLabelAdded(LabelAdded $labelAdded)
    {
        $document = $this->getDocument($labelAdded->getLabelId());

        if ($document) {
            $roleDetails = $this->getRoleDetails($document);
            $roleDetails[$labelAdded->getUuid()->toNative()] = $labelAdded->getUuid()->toNative();
            $document = $document->withBody($roleDetails);
            $this->repository->save($document);
        }
    }

    /**
     * @param LabelRemoved $labelRemoved
     */
    public function applyLabelRemoved(LabelRemoved $labelRemoved)
    {
        $document = $this->getDocument($labelRemoved->getLabelId());

        if ($document) {
            $roleDetails = $this->getRoleDetails($document);
            unset($roleDetails[$labelRemoved->getUuid()->toNative()]);
            $document = $document->withBody($roleDetails);
            $this->repository->save($document);
        }
    }

    /**
     * @param LabelCreated $labelCreated
     */
    public function applyCreated(LabelCreated $labelCreated)
    {
        $document = $this->createNewDocument($labelCreated->getUuid());
        $this->repository->save($document);
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
    private function getRoleDetails(JsonDocument $document)
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
