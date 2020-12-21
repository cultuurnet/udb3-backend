<?php declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

final class OfferDocumentRepository implements DocumentRepository
{
    /**
     * @var DocumentRepository
     */
    private $eventDocumentRepository;

    /**
     * @var DocumentRepository
     */
    private $placeDocumentRepository;

    public function __construct(DocumentRepository $eventDocumentRepository, DocumentRepository $placeDocumentRepository)
    {
        $this->eventDocumentRepository = $eventDocumentRepository;
        $this->placeDocumentRepository = $placeDocumentRepository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        try {
            return $this->eventDocumentRepository->fetch($id, $includeMetadata);
        } catch (DocumentDoesNotExist $documentDoesNotExist) {
            if ($documentDoesNotExist->isGone()) {
                throw $documentDoesNotExist;
            }
            return $this->placeDocumentRepository->fetch($id, $includeMetadata);
        }
    }

    public function get(string $id, bool $includeMetadata = false): ?JsonDocument
    {
        throw new \RuntimeException('The `get` method is not supported on OfferDocumentRepository.');
    }

    public function save(JsonDocument $readModel): void
    {
        $json = $readModel->getAssocBody();

        if (empty($json['@context'])) {
            throw new \RuntimeException('A context is required');
        }

        if ($json['@context'] === Context::EVENT) {
            $this->eventDocumentRepository->save($readModel);
            return;
        }

        if ($json['@context'] === Context::PLACE) {
            $this->placeDocumentRepository->save($readModel);
            return;
        }

        throw new \RuntimeException('A context should be `context/place` or `context/event`');
    }

    public function remove($id): void
    {
        try {
            $this->eventDocumentRepository->fetch($id);
            $this->eventDocumentRepository->remove($id);
        } catch (DocumentDoesNotExist $documentDoesNotExist) {
            if ($documentDoesNotExist->isGone()) {
                throw $documentDoesNotExist;
            }
            $this->placeDocumentRepository->fetch($id);
            $this->placeDocumentRepository->remove($id);
        }
    }
}
