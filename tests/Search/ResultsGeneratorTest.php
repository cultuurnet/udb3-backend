<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ResultsGeneratorTest extends TestCase
{
    /**
     * @var SearchServiceInterface&MockObject
     */
    private $searchService;

    private Sorting $sorting;

    private ResultsGenerator $generator;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    private string $query;

    public function setUp(): void
    {
        $this->searchService = $this->createMock(SearchServiceInterface::class);

        $this->sorting = new Sorting('created', 'asc');

        $this->generator = new ResultsGenerator(
            $this->searchService,
            $this->sorting,
            2
        );

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->generator->setLogger($this->logger);

        $this->query = 'city:leuven';
    }

    /**
     * @test
     */
    public function it_can_return_a_count_for_a_query(): void
    {
        $givenQuery = '*';
        $expectedCount = 12345678;

        $this->searchService->expects($this->once())
            ->method('search')
            ->with($givenQuery, 1, 0)
            ->willReturn(
                new Results(
                    new ItemIdentifiers(
                        new ItemIdentifier(
                            new Url('http://io.uitdatabank.dev/event/0d325df2-da0a-4d4e-957f-60220c2f9baf'),
                            '0d325df2-da0a-4d4e-957f-60220c2f9baf',
                            ItemType::event()
                        )
                    ),
                    $expectedCount
                )
            );

        $actualCount = $this->generator->count($givenQuery);

        $this->assertEquals($expectedCount, $actualCount);
    }

    /**
     * @test
     */
    public function it_has_configurable_sorting_and_page_size_with_default_values(): void
    {
        $generator = new ResultsGenerator($this->searchService);

        $this->assertEquals(new Sorting('created', 'asc'), $generator->getSorting());
        $this->assertEquals(10, $generator->getPageSize());

        /* @var ResultsGenerator $generator */
        $generator = $generator->withSorting(new Sorting('created', 'desc'))
            ->withPageSize(5);

        $this->assertEquals(new Sorting('created', 'desc'), $generator->getSorting());
        $this->assertEquals(5, $generator->getPageSize());
    }

    /**
     * @test
     *
     * @dataProvider pagedResultsDataProvider
     *
     * @param int $givenPageSize
     *   Number of results per page.
     *
     * @param array $givenPages
     *   Multiple pages with results per page.
     *
     * @param array $expectedResults
     *   All results in a single array.
     *
     * @param array $expectedLogs
     *   All expected logs in a single array.
     */
    public function it_loops_over_all_pages_and_yields_each_unique_result_while_logging_duplicates(
        int $givenPageSize,
        array $givenPages,
        array $expectedResults,
        array $expectedLogs = []
    ): void {
        $currentPage = 0;
        $totalPages = count($givenPages);
        $totalResults = count($expectedResults);
        $actualResults = [];
        $actualLogs = [];

        $this->searchService->expects($this->exactly($totalPages))
            ->method('search')
            ->willReturnCallback(
                function (
                    $query,
                    $pageSize,
                    $start,
                    $sorting
                ) use (
                    $givenPageSize,
                    $givenPages,
                    $totalResults,
                    &$currentPage
                ) {
                    // Do some assertions on the provided arguments here.
                    // We can't use withConsecutive() on the mock object
                    // because we have a variable number of method calls and
                    // withConsecutive() doesn't allow an array of arguments.
                    $this->assertEquals($this->query, $query);
                    $this->assertEquals($givenPageSize, $pageSize);
                    $this->assertEquals($givenPageSize * $currentPage, $start);
                    $this->assertEquals($this->sorting->toArray(), $sorting);

                    $pageResults = $givenPages[$currentPage];

                    $currentPage++;

                    return new Results(
                        new ItemIdentifiers(...$pageResults),
                        $totalResults
                    );
                }
            );

        $this->logger->expects($this->any())
            ->method('error')
            ->willReturnCallback(
                function ($type, $data) use (&$actualLogs): void {
                    $this->assertEquals('query_duplicate_event', $type);

                    $error = $data['error'];
                    $actualLogs[] = $error;
                }
            );

        $generator = $this->generator->withPageSize($givenPageSize);
        foreach ($generator->search($this->query) as $result) {
            $actualResults[] = $result;
        }

        $this->assertEquals($expectedResults, $actualResults);
        $this->assertEquals($expectedLogs, $actualLogs);
    }


    public function pagedResultsDataProvider(): array
    {
        $event1 = new ItemIdentifier(
            new Url('http://du.de/event/1'),
            '1',
            ItemType::event()
        );

        $event2 = new ItemIdentifier(
            new Url('http://du.de/event/2'),
            '2',
            ItemType::event()
        );

        $event3 = new ItemIdentifier(
            new Url('http://du.de/event/3'),
            '3',
            ItemType::event()
        );

        $event4 = new ItemIdentifier(
            new Url('http://du.de/event/4'),
            '4',
            ItemType::event()
        );

        $event5 = new ItemIdentifier(
            new Url('http://du.de/event/5'),
            '5',
            ItemType::event()
        );

        $event6 = new ItemIdentifier(
            new Url('http://du.de/event/6'),
            '6',
            ItemType::event()
        );

        $event7 = new ItemIdentifier(
            new Url('http://du.de/event/7'),
            '7',
            ItemType::event()
        );

        return [
            [
                // 2 results per page, 1 result.
                2,
                [
                    [
                        $event1,
                    ],
                ],
                [
                    $event1,
                ],
            ],
            [
                // 2 results per page, 2 results.
                2,
                [
                    [
                        $event1,
                        $event2,
                    ],
                ],
                [
                    $event1,
                    $event2,
                ],
            ],
            [
                // 2 results per page, 5 results.
                2,
                [
                    [
                        $event1,
                        $event2,
                    ],
                    [
                        $event3,
                        $event4,
                    ],
                    [
                        $event5,
                    ],
                ],
                [
                    $event1,
                    $event2,
                    $event3,
                    $event4,
                    $event5,
                ],
            ],
            [
                // 2 results per page, 6 results.
                2,
                [
                    [
                        $event1,
                        $event2,
                    ],
                    [
                        $event3,
                        $event4,
                    ],
                    [
                        $event5,
                        $event6,
                    ],
                ],
                [
                    $event1,
                    $event2,
                    $event3,
                    $event4,
                    $event5,
                    $event6,
                ],
            ],
            [
                // 3 results per page, 5 results.
                3,
                [
                    [
                        $event1,
                        $event2,
                        $event3,
                    ],
                    [
                        $event4,
                        $event5,
                    ],
                ],
                [
                    $event1,
                    $event2,
                    $event3,
                    $event4,
                    $event5,
                ],
            ],
            [
                // 3 results per page, 6 results.
                3,
                [
                    [
                        $event1,
                        $event2,
                        $event3,
                    ],
                    [
                        $event4,
                        $event5,
                        $event6,
                    ],
                ],
                [
                    $event1,
                    $event2,
                    $event3,
                    $event4,
                    $event5,
                    $event6,
                ],
            ],
            [
                // 5 results per page, 6 results.
                5,
                [
                    [
                        $event1,
                        $event2,
                        $event3,
                        $event4,
                        $event5,
                    ],
                    [
                        $event6,
                    ],
                ],
                [
                    $event1,
                    $event2,
                    $event3,
                    $event4,
                    $event5,
                    $event6,
                ],
            ],
            [
                // 3 results per page, 9 results, 2 duplicates.
                3,
                [
                    [
                        $event1,
                        $event2,
                        $event1,
                    ],
                    [
                        $event3,
                        $event4,
                        $event5,
                    ],
                    [
                        $event4,
                        $event6,
                        $event7,
                    ],
                ],
                [
                    $event1,
                    $event2,
                    $event3,
                    $event4,
                    $event5,
                    $event6,
                    $event7,
                ],
                [
                    'Found duplicate offer 1 on page 0, occurred first time on page 0.',
                    'Found duplicate offer 4 on page 2, occurred first time on page 1.',
                ],
            ],
        ];
    }
}
