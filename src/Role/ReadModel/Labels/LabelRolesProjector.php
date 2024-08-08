<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Events\Created as LabelCreated;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\ReadModel\RoleProjector;

class LabelRolesProjector extends RoleProjector
{
    public function applyLabelAdded(LabelAdded $labelAdded): void
    {
        $document = $this->getDocument($labelAdded->getLabelId());

        if ($document) {
            $roleDetails = $this->getRoleDetails($document);
            $roleDetails[$labelAdded->getUuid()->toString()] = $labelAdded->getUuid()->toString();
            $document = $document->withAssocBody($roleDetails);
            $this->repository->save($document);
        }
    }


    public function applyLabelRemoved(LabelRemoved $labelRemoved): void
    {
        $document = $this->getDocument($labelRemoved->getLabelId());

        if ($document) {
            $roleDetails = $this->getRoleDetails($document);
            unset($roleDetails[$labelRemoved->getUuid()->toString()]);
            $document = $document->withAssocBody($roleDetails);
            $this->repository->save($document);
        }
    }


    public function applyCreated(LabelCreated $labelCreated): void
    {
        $document = $this->createNewDocument($labelCreated->getUuid());
        $this->repository->save($document);
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
    private function getRoleDetails(JsonDocument $document)
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
            Json::encode([])
        );
        return $document;
    }
}
