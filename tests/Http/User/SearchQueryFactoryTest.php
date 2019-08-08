<?php

namespace CultuurNet\UDB3\Http\User;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SearchQueryFactoryTest extends TestCase
{
    /**
     * @var SearchQueryFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new SearchQueryFactory();
    }

    /**
     * @test
     */
    public function it_creates_a_search_query_object_from_a_request()
    {
        $request = new Request(
            [
                'start' => 20,
                'limit' => 10,
                'email' => '*@cultuurnet.be',
            ]
        );

        $expectedSearchQuery = new \CultureFeed_SearchUsersQuery();
        $expectedSearchQuery->start = 20;
        $expectedSearchQuery->max = 10;
        $expectedSearchQuery->mbox = '*@cultuurnet.be';
        $expectedSearchQuery->mboxIncludePrivate = true;

        $actualSearchQuery = $this->factory->createSearchQueryfromRequest($request);

        $this->assertEquals($expectedSearchQuery, $actualSearchQuery);
    }

    /**
     * @test
     * @dataProvider searchQueryPaginationDataProvider
     *
     * @param Request $request
     * @param int $expectedStart
     * @param int $expectedLimit
     * @param int $expectedPageNumber
     */
    public function it_extracts_start_and_limit_and_page_number_from_request_query_parameters(
        Request $request,
        $expectedStart,
        $expectedLimit,
        $expectedPageNumber
    ) {
        $actualStart = $this->factory->getStartFromRequest($request);
        $actualLimit = $this->factory->getLimitFromRequest($request);
        $actualPageNumber = $this->factory->createPageNumberFromRequest($request);

        $this->assertEquals($expectedStart, $actualStart);
        $this->assertEquals($expectedLimit, $actualLimit);
        $this->assertEquals($expectedPageNumber, $actualPageNumber);
    }

    /**
     * @return array
     */
    public function searchQueryPaginationDataProvider()
    {
        return [
            [
                new Request(),
                0,
                30,
                1,
            ],
            [
                new Request(['limit' => 10]),
                0,
                10,
                1,
            ],
            [
                new Request(['start' => 30]),
                30,
                30,
                2,
            ],
            [
                new Request(['start' => 20, 'limit' => 10]),
                20,
                10,
                3,
            ],
        ];
    }
}
