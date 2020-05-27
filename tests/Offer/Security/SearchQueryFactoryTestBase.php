<?php

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\Search\Parameter\Query;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

abstract class SearchQueryFactoryTestBase extends TestCase
{
    /**
     * @var SearchQueryFactoryInterface
     */
    protected $searchQueryFactory;

    /**
     * @test
     */
    public function it_creates_a_query_from_a_constraint()
    {
        $constraint = new StringLiteral('zipCode:3000 OR zipCode:3010');
        $offerId = new StringLiteral('offerId');

        $query = $this->searchQueryFactory->createFromConstraint(
            $constraint,
            $offerId
        );

        $expectedQuery = new Query($this->createQueryString($constraint, $offerId));

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_creates_a_query_from_constraints()
    {
        $constraint1 = new StringLiteral('zipCode:3000 OR zipCode:3010');
        $constraint2 = new StringLiteral('zipCode:3271 OR zipCode:3271');

        $offerId = new StringLiteral('offerId');

        $query = $this->searchQueryFactory->createFromConstraints(
            [$constraint1, $constraint2],
            $offerId
        );

        $queryStr1 = $this->createQueryString($constraint1, $offerId);
        $queryStr2 = $this->createQueryString($constraint2, $offerId);
        $expectedQuery = new Query($queryStr1 . ' OR ' . $queryStr2);

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @param StringLiteral $constraint
     * @param StringLiteral $offerId
     * @return string
     */
    abstract protected function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    );
}
