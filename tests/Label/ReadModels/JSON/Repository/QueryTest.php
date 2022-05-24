<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

final class QueryTest extends TestCase
{
    public const NAME = 'name';
    public const USER_ID = 'userId';
    public const OFFSET = 5;
    public const LIMIT = 10;

    private Query $query;

    protected function setUp(): void
    {
        $this->query = new Query(
            self::NAME,
            self::USER_ID,
            self::OFFSET,
            self::LIMIT
        );
    }

    /**
     * @test
     */
    public function it_stores_a_value(): void
    {
        $this->assertEquals(
            new StringLiteral(self::NAME),
            $this->query->getValue()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_user_id(): void
    {
        $this->assertEquals(
            new StringLiteral(self::USER_ID),
            $this->query->getUserId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_offset(): void
    {
        $this->assertEquals(
            self::OFFSET,
            $this->query->getOffset()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_limit(): void
    {
        $this->assertEquals(
            self::LIMIT,
            $this->query->getLimit()
        );
    }

    /**
     * @test
     */
    public function it_has_a_default_user_id_of_null(): void
    {
        $query = new Query(self::NAME);

        $this->assertEquals(null, $query->getUserId());
    }

    /**
     * @test
     */
    public function it_has_a_default_offset_of_null(): void
    {
        $query = new Query(self::NAME);

        $this->assertEquals(null, $query->getOffset());
    }

    /**
     * @test
     */
    public function it_requires_a_positive_offset(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Query(
            self::NAME,
            self::USER_ID,
            -1,
            self::LIMIT
        );
    }

    /**
     * @test
     */
    public function it_has_a_default_limit_of_null(): void
    {
        $query = new Query(self::NAME);

        $this->assertEquals(null, $query->getLimit());
    }

    /**
     * @test
     */
    public function it_requires_a_positive_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Query(
            self::NAME,
            self::USER_ID,
            self::OFFSET,
            -1
        );
    }
}
