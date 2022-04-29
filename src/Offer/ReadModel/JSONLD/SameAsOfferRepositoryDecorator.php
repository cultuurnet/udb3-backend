<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SameAsInterface;

final class SameAsOfferRepositoryDecorator extends DocumentRepositoryDecorator
{
    private SameAsInterface $sameAs;

    public function __construct(DocumentRepository $documentRepository, SameAsInterface $sameAs)
    {
        parent::__construct($documentRepository);
        $this->sameAs = $sameAs;
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
        $id = $jsonDocument->getId();

        return $jsonDocument->applyAssoc(
            function (array $json) use ($id) {
                $json['sameAs'] = $this->sameAs->generateSameAs($id, $json['name']['nl']);
                return $json;
            }
        );
    }
}
