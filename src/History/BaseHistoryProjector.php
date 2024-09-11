<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\History;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

abstract class BaseHistoryProjector implements EventListener
{
    private DocumentRepository $documentRepository;

    public function __construct(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    protected function loadDocumentFromRepositoryByEventId(string $eventId): JsonDocument
    {
        try {
            $historyDocument = $this->documentRepository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            $historyDocument = new JsonDocument($eventId, '[]');
        }

        return $historyDocument;
    }

    protected function writeHistory(string $eventId, Log $log): void
    {
        $historyDocument = $this->loadDocumentFromRepositoryByEventId($eventId);

        $history = (array) $historyDocument->getBody();

        $history[$log->getUniqueKey()] = $log;

        $this->documentRepository->save(
            $historyDocument->withBody((object) $history)
        );
    }
}
