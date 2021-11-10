<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class RecommendationsForEnrichedOfferRepository extends DocumentRepositoryDecorator
{
    private RecommendationsRepository $recommendationsRepository;

    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        RecommendationsRepository $recommendationsRepository,
        IriGeneratorInterface $iriGenerator,
        DocumentRepository $documentRepository
    ) {
        parent::__construct($documentRepository);
        $this->recommendationsRepository = $recommendationsRepository;
        $this->iriGenerator = $iriGenerator;
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
        $recommendations = $this->recommendationsRepository->getByRecommendedEvent($jsonDocument->getId());

        if ($recommendations->isEmpty()) {
            return $jsonDocument;
        }

        return $jsonDocument->applyAssoc(
            function (array $body) use ($recommendations) {
                $body['metadata']['recommendationFor'] = $this->recommendationsToArray($recommendations);
                return $body;
            }
        );
    }

    private function recommendationsToArray(Recommendations $recommendations): array
    {
        return array_map(
            fn (Recommendation $recommendation) => [
                'event' => $this->iriGenerator->iri($recommendation->getEvent()),
                'score' => $recommendation->getScore(),
            ],
            $recommendations->toArray()
        );
    }
}
