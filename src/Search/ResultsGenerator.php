<?php

namespace CultuurNet\UDB3\Search;

use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ResultsGenerator implements LoggerAwareInterface, ResultsGeneratorInterface
{
    use LoggerAwareTrait;

    /**
     * Default sorting method because it's ideal for getting consistent paging
     * results.
     */
    private const SORT_CREATED_ASC = ['created' => 'asc'];

    /**
     * @var SearchServiceInterface
     */
    private $searchService;

    /**
     * @var string
     */
    private $sorting;

    /**
     * @var int
     */
    private $pageSize;

    public function __construct(
        SearchServiceInterface $searchService,
        array $sorting = null,
        int $pageSize = 10
    ) {
        if ($sorting === null) {
            $sorting = self::SORT_CREATED_ASC;
        }

        $this->searchService = $searchService;
        $this->sorting = $sorting;
        $this->pageSize = $pageSize;

        // Set a default logger so we don't need to check if a logger is set
        // when we actually try to log something. This can easily be overridden
        // from outside as this method is public.
        $this->setLogger(new NullLogger());
    }

    public function withSorting(array $sorting): ResultsGenerator
    {
        $c = clone $this;
        $c->sorting = $sorting;
        return $c;
    }

    public function getSorting(): array
    {
        return $this->sorting;
    }

    public function withPageSize(int $pageSize): ResultsGenerator
    {
        $c = clone $this;
        $c->pageSize = $pageSize;
        return $c;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function search(string $query): Generator
    {
        $currentPage = 0;
        $ids = [];

        if ($this->searchService instanceof LoggerAwareInterface) {
            $this->searchService->setLogger($this->logger);
        }

        do {
            $results = $this->searchService->search(
                $query,
                $this->pageSize,
                $this->pageSize * $currentPage,
                $this->sorting
            );

            $total = $results->getTotalItems()->toNative();

            $this->logger->info('Search API reported ' . $total . ' results');

            foreach ($results->getItems() as $item) {
                $id = $item->getId();

                if (!isset($ids[$id])) {
                    // Store result id with current page in case we run into
                    // the same id again later.
                    $ids[$id] = $currentPage;
                    yield $id => $item;
                } else {
                    $this->logger->error(
                        'query_duplicate_event',
                        array(
                            'query' => $query,
                            'error' => "Found duplicate offer {$id} on page {$currentPage}, " .
                                "occurred first time on page {$ids[$id]}.",
                        )
                    );
                }
            }

            $currentPage++;
        } while ($currentPage < ceil($total / $this->pageSize));
    }
}
