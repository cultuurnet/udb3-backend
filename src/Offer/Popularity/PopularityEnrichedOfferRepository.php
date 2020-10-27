<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class PopularityEnrichedOfferRepository extends DocumentRepositoryDecorator
{
    /**
     * @var PopularityRepository
     */
    private $popularityRepository;

    public function __construct(PopularityRepository $popularityRepository, DocumentRepository $documentRepository)
    {
        parent::__construct($documentRepository);
        $this->popularityRepository = $popularityRepository;
    }

    public function get(string $id, bool $includeMetadata = false): ?JsonDocument
    {
        $jsonDocument = parent::get($id, $includeMetadata);

        if ($includeMetadata && $jsonDocument instanceof JsonDocument) {
            $popularity = $this->popularityRepository->get($id);

            $jsonDocument = $jsonDocument->applyAssoc(
                function (array $body) use ($popularity) {
                    $body['metadata']['popularity'] = $popularity->toNative();
                    return $body;
                }
            );
        }

        return $jsonDocument;
    }
}
