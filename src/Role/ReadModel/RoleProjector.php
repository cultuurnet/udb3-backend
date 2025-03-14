<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class RoleProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    protected DocumentRepository $repository;

    public function __construct(DocumentRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function saveNewDocument(string $uuid, callable $fn): void
    {
        $document = $this
            ->newDocument($uuid)
            ->apply($fn);

        $this->repository->save($document);
    }

    protected function loadDocumentFromRepositoryByUuid(string $uuid): JsonDocument
    {
        try {
            $document = $this->repository->fetch($uuid);
        } catch (DocumentDoesNotExist $e) {
            return $this->newDocument($uuid);
        }

        return $document;
    }

    protected function newDocument(string $uuid): JsonDocument
    {
        $document = new JsonDocument($uuid);

        $json = $document->getBody();
        $json->uuid = $uuid;

        return $document->withBody($json);
    }
}
