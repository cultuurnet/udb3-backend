<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ResultsGenerator implements LoggerAwareInterface, ResultsGeneratorInterface
{
    use LoggerAwareTrait;

    private const DEFAULT_SORTING_PROPERTY = 'created';
    private const DEFAULT_SORTING_ORDER = 'asc';

    private const COUNT_PAGE_LIMIT = 1;
    private const COUNT_PAGE_START = 0;

    private SearchServiceInterface $searchService;

    private Sorting $sorting;

    private int $pageSize;

    public function __construct(
        SearchServiceInterface $searchService,
        Sorting $sorting = null,
        int $pageSize = 10
    ) {
        if ($sorting === null) {
            $sorting = new Sorting(self::DEFAULT_SORTING_PROPERTY, self::DEFAULT_SORTING_ORDER);
        }

        $this->searchService = $searchService;
        $this->sorting = $sorting;
        $this->pageSize = $pageSize;

        // Set a default logger so we don't need to check if a logger is set
        // when we actually try to log something. This can easily be overridden
        // from outside as this method is public.
        $this->setLogger(new NullLogger());
    }

    public function withSorting(Sorting $sorting): ResultsGenerator
    {
        $c = clone $this;
        $c->sorting = $sorting;
        return $c;
    }

    public function getSorting(): Sorting
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

    public function count(string $query): int
    {
        return $this->searchService
            ->search($query, self::COUNT_PAGE_LIMIT, self::COUNT_PAGE_START)
            ->getTotalItems();
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
                $this->sorting->toArray()
            );

            $total = $results->getTotalItems();

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
                        [
                            'query' => $query,
                            'error' => "Found duplicate offer {$id} on page {$currentPage}, " .
                                "occurred first time on page {$ids[$id]}.",
                        ]
                    );
                }
            }

            $currentPage++;
        } while ($currentPage < ceil($total / $this->pageSize));
    }
}
