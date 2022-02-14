<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class QueryTest extends TestCase
{
    public const NAME = 'name';
    public const USER_ID = 'userId';
    public const OFFSET = 5;
    public const LIMIT = 10;

    /**
     * @var Query
     */
    private $query;

    protected function setUp()
    {
        $this->query = new Query(
            new StringLiteral(self::NAME),
            new StringLiteral(self::USER_ID),
            self::OFFSET,
            self::LIMIT
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
            self::OFFSET,
            $this->query->getOffset()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_limit()
    {
        $this->assertEquals(
            self::LIMIT,
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
