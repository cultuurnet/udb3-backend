<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

final class Sapi3SearchQueryFactoryTest extends TestCase
{
    /**
     * @var Sapi3SearchQueryFactory
     */
    protected $searchQueryFactory;

    protected function setUp()
    {
        $this->searchQueryFactory = new Sapi3SearchQueryFactory();
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

        $expectedQuery = '((zipCode:3000 OR zipCode:3010) AND id:offerId) OR ((zipCode:3271 OR zipCode:3271) AND id:offerId)';

        $this->assertEquals($expectedQuery, $query);
    }
}
