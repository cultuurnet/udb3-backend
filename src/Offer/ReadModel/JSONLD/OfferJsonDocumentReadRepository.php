<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

/**
 * Makes it possible to dynamically fetch either an event document or place document without needing to inject two
 * DocumentRepository instances and implementing the logic to switch between them in every place where this is useful.
 */
final class OfferJsonDocumentReadRepository
{
    private DocumentRepository $eventDocumentRepository;
    private DocumentRepository $placeDocumentRepository;

    public function __construct(DocumentRepository $eventDocumentRepository, DocumentRepository $placeDocumentRepository)
    {
        $this->eventDocumentRepository = $eventDocumentRepository;
        $this->placeDocumentRepository = $placeDocumentRepository;
    }

    public function fetch(OfferType $offerType, string $id, bool $includeMetadata = false): JsonDocument
    {
        if ($offerType->sameAs(OfferType::event())) {
            return $this->eventDocumentRepository->fetch($id, $includeMetadata);
        }
        if ($offerType->sameAs(OfferType::place())) {
            return $this->placeDocumentRepository->fetch($id, $includeMetadata);
        }

        throw DocumentDoesNotExist::withId($id);
    }
}
