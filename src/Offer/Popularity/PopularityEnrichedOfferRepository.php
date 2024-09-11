<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class PopularityEnrichedOfferRepository extends DocumentRepositoryDecorator
{
    private PopularityRepository $popularityRepository;

    public function __construct(PopularityRepository $popularityRepository, DocumentRepository $documentRepository)
    {
        parent::__construct($documentRepository);
        $this->popularityRepository = $popularityRepository;
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
        $popularity = $this->popularityRepository->get($jsonDocument->getId());

        return $jsonDocument->applyAssoc(
            function (array $body) use ($popularity) {
                $body['metadata']['popularity'] = $popularity->toNative();
                return $body;
            }
        );
    }
}
