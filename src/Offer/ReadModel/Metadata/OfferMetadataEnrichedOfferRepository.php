<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class OfferMetadataEnrichedOfferRepository extends DocumentRepositoryDecorator
{
    private OfferMetadataRepository $offerMetadataRepository;

    public function __construct(OfferMetadataRepository $offerMetadataRepository, DocumentRepository $documentRepository)
    {
        parent::__construct($documentRepository);
        $this->offerMetadataRepository = $offerMetadataRepository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $jsonDocument = parent::fetch($id, $includeMetadata);

        if ($includeMetadata) {
            $jsonDocument = $this->enrich($jsonDocument);
        }

        return $jsonDocument;
    }

    private function enrich(JsonDocument $jsonDocument): JsonDocument
    {
        try {
            $offerMetadata = $this->offerMetadataRepository->get($jsonDocument->getId());
        } catch (EntityNotFoundException $e) {
            $offerMetadata = OfferMetadata::default($jsonDocument->getId());
        }

        return $jsonDocument->applyAssoc(
            function (array $body) use ($offerMetadata) {
                $body['metadata']['createdByApiConsumer'] = $offerMetadata->getCreatedByApiConsumer();
                return $body;
            }
        );
    }
}
