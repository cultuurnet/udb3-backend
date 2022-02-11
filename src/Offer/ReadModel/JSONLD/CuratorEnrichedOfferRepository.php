<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticleSearch;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Psr\Log\LoggerInterface;

final class CuratorEnrichedOfferRepository extends DocumentRepositoryDecorator
{
    private NewsArticleRepository $newsArticleRepository;
    private LoggerInterface $logger;

    private array $curatorLabels;

    public function __construct(
        DocumentRepository $documentRepository,
        NewsArticleRepository $newsArticleRepository,
        LoggerInterface $logger,
        array $curatorLabels
    ) {
        parent::__construct($documentRepository);

        $this->newsArticleRepository = $newsArticleRepository;
        $this->logger = $logger;
        // The keys of the curator labels has mixed casing.
        // To work around this every key and every curator get converted to lower case.
        $this->curatorLabels = array_change_key_case($curatorLabels, CASE_LOWER);
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        return parent::fetch($id, $includeMetadata)->applyAssoc(
            fn (array $json) => $this->applyCuratorLabels($json, $id)
        );
    }

    public function applyCuratorLabels(array $json, string $id): array
    {
        $newsArticles = $this->newsArticleRepository->search(new NewsArticleSearch(null, $id, null));

        $curators = array_unique(array_map(
            fn (NewsArticle $newsArticle) => strtolower($newsArticle->getPublisher()),
            $newsArticles->toArray()
        ));

        // Take into account projections that don't have hiddenLabels and remove all known curator labels.
        $hiddenLabels = array_diff($json['hiddenLabels'] ?? [], $this->curatorLabels);

        // Apply curator labels based on the publishers of the news articles.
        // This should also solve issues with the old code where deletes or updates of news articles were not handled.
        foreach ($curators as $curator) {
            if (!isset($this->curatorLabels[$curator])) {
                $this->logger->error('Curator label for "' . $curator . '" missing in config!');
                continue;
            }
            $hiddenLabels[] = $this->curatorLabels[$curator];
        }

        // Make sure to not add an empty list of hiddenLabels.
        if (count($hiddenLabels) > 0) {
            $json['hiddenLabels'] = $hiddenLabels;
        }

        return $json;
    }
}
