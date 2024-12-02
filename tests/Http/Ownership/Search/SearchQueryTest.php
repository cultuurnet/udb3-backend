<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership\Search;

use PHPUnit\Framework\TestCase;

class SearchQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_stores_the_parameters_start_and_limit(): void
    {
        $searchQuery = new SearchQuery([], 2, 20);

        $this->assertEquals(2, $searchQuery->getStart());
        $this->assertEquals(20, $searchQuery->getLimit());
    }

    /**
     * @test
     */
    public function it_stores_the_parameters(): void
    {
        $parameters = [
            new SearchParameter('itemId', 'value'),
            new SearchParameter('state', 'rejected'),
        ];

        $searchQuery = new SearchQuery($parameters);

        $this->assertEquals($parameters, $searchQuery->getParameters());
    }

    /**
     * @test
     */
    public function it_stores_the_parameters_start_and_limit_with_default_values(): void
    {
        $searchQuery = new SearchQuery([]);

        $this->assertEquals(0, $searchQuery->getStart());
        $this->assertEquals(50, $searchQuery->getLimit());
    }
}
