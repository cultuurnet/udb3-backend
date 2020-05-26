<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use PHPUnit\Framework\TestCase;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class QueryTest extends TestCase
{
    const NAME = 'name';
    const USER_ID = 'userId';
    const OFFSET = 5;
    const LIMIT = 10;

    /**
     * @var Query
     */
    private $query;

    protected function setUp()
    {
        $this->query = new Query(
            new StringLiteral(self::NAME),
            new StringLiteral(self::USER_ID),
            new Natural(self::OFFSET),
            new Natural(self::LIMIT)
        );
    }

    /**
     * @test
     */
    public function it_stores_a_value()
    {
        $this->assertEquals(
            new StringLiteral(self::NAME),
            $this->query->getValue()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_user_id()
    {
        $this->assertEquals(
            new StringLiteral(self::USER_ID),
            $this->query->getUserId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_offset()
    {
        $this->assertEquals(
            new Natural(self::OFFSET),
            $this->query->getOffset()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_limit()
    {
        $this->assertEquals(
            new Natural(self::LIMIT),
            $this->query->getLimit()
        );
    }

    /**
     * @test
     */
    public function it_has_a_default_user_id_of_null()
    {
        $query = new Query(new StringLiteral(self::NAME));

        $this->assertEquals(null, $query->getUserId());
    }

    /**
     * @test
     */
    public function it_has_a_default_offset_of_null()
    {
        $query = new Query(new StringLiteral(self::NAME));

        $this->assertEquals(null, $query->getOffset());
    }

    /**
     * @test
     */
    public function it_has_a_default_limit_of_null()
    {
        $query = new Query(new StringLiteral(self::NAME));

        $this->assertEquals(null, $query->getLimit());
    }
}
