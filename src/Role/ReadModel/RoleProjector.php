<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class RoleProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var DocumentRepository
     */
    protected $repository;

    /**
     * Projector constructor.
     */
    public function __construct(DocumentRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $uuid
     */
    protected function saveNewDocument($uuid, callable $fn)
    {
        $document = $this
            ->newDocument($uuid)
            ->apply($fn);

        $this->repository->save($document);
    }

    /**
     * @param string $uuid
     * @return JsonDocument
     */
    protected function loadDocumentFromRepositoryByUuid($uuid)
    {
        $document = $this->repository->get($uuid);

        if (!$document) {
            return $this->newDocument($uuid);
        }

        return $document;
    }

    /**
     * @param string $uuid
     * @return JsonDocument
     */
    protected function newDocument($uuid)
    {
        $document = new JsonDocument($uuid);

        $json = $document->getBody();
        $json->uuid = $uuid;

        return $document->withBody($json);
    }
}
