<?php

namespace CultuurNet\UDB3\Role\ReadModel;

use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class RoleProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var DocumentRepositoryInterface
     */
    protected $repository;

    /**
     * Projector constructor.
     * @param DocumentRepositoryInterface $repository
     */
    public function __construct(DocumentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $uuid
     * @param callable $fn
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
