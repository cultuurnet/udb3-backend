<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class ContributorEnrichedRepository extends DocumentRepositoryDecorator
{
    private ContributorRepository $contributorRepository;

    public function __construct(ContributorRepository $contributorRepository, DocumentRepository $documentRepository)
    {
        parent::__construct($documentRepository);
        $this->contributorRepository = $contributorRepository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $jsonDocument = parent::fetch($id, $includeMetadata);
        // TODO: Check permission
        $hasCorrectPermission = true;

        if ($hasCorrectPermission) {
            $jsonDocument = $this->enrich($jsonDocument);
        }

        return $jsonDocument;
    }

    private function enrich(JsonDocument $jsonDocument): JsonDocument
    {
        $contributors = $this->contributorRepository->getContributors(new UUID($jsonDocument->getId()));

        return $jsonDocument->applyAssoc(
            function (array $body) use ($contributors) {
                $body['contributors'] = $contributors->toStringArray();
                return $body;
            }
        );
    }
}
