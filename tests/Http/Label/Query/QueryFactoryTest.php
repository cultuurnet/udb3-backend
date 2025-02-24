<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use PHPUnit\Framework\TestCase;

final class QueryFactoryTest extends TestCase
{
    public const QUERY_VALUE = 'label';
    public const USER_ID_VALUE = 'userId';
    public const START_VALUE = 5;
    public const LIMIT_VALUE = 10;

    private QueryFactory $queryFactory;

    protected function setUp(): void
    {
        $this->queryFactory = new QueryFactory(self::USER_ID_VALUE);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s&%s=%s&%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                    QueryFactory::START,
                    self::START_VALUE,
                    QueryFactory::LIMIT,
                    self::LIMIT_VALUE
                )
            )
            ->build('GET');

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            self::USER_ID_VALUE,
            self::START_VALUE,
            self::LIMIT_VALUE
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_no_start(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s&&%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                    QueryFactory::LIMIT,
                    self::LIMIT_VALUE
                )
            )
            ->build('GET');

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            self::USER_ID_VALUE,
            0,
            self::LIMIT_VALUE
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_no_limit(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s&%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                    QueryFactory::START,
                    self::START_VALUE
                )
            )
            ->build('GET');

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            self::USER_ID_VALUE,
            self::START_VALUE,
            QueryFactory::MAX_LIMIT,
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_no_start_and_no_limit(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                )
            )
            ->build('GET');

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            self::USER_ID_VALUE,
            0,
            QueryFactory::MAX_LIMIT,
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_get_query_from_request_with_zero_start_and_zero_limit(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s&%s=%s&%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                    QueryFactory::START,
                    0,
                    QueryFactory::LIMIT,
                    0
                )
            )
            ->build('GET');

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            self::USER_ID_VALUE,
            0,
            0
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_enforces_a_maximum_limit(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s&%s=%s&%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                    QueryFactory::START,
                    0,
                    QueryFactory::LIMIT,
                    9223372036854775807,
                )
            )
            ->build('GET');

        $query = $this->queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            self::USER_ID_VALUE,
            0,
            30,
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     */
    public function it_can_return_a_query_without_user_id(): void
    {
        $queryFactory = new QueryFactory(null);

        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s&%s=%s&%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                    QueryFactory::START,
                    self::START_VALUE,
                    QueryFactory::LIMIT,
                    self::LIMIT_VALUE
                )
            )
            ->build('GET');

        $query = $queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            null,
            self::START_VALUE,
            self::LIMIT_VALUE
        );

        $this->assertEquals($expectedQuery, $query);
    }

    /**
     * @test
     * @dataProvider suggestionDataProvider
     * @param string|int|bool $queryValue
     */
    public function it_can_return_a_query_with_suggestion($queryValue, bool $suggestion): void
    {
        $queryFactory = new QueryFactory(null);

        $request = (new Psr7RequestBuilder())
            ->withUriFromString(
                sprintf(
                    '/?%s=%s&%s=%s&%s=%s&%s=%s',
                    QueryFactory::QUERY,
                    self::QUERY_VALUE,
                    QueryFactory::START,
                    self::START_VALUE,
                    QueryFactory::LIMIT,
                    self::LIMIT_VALUE,
                    QueryFactory::SUGGESTION,
                    $queryValue
                )
            )
            ->build('GET');

        $query = $queryFactory->createFromRequest($request);

        $expectedQuery = new Query(
            self::QUERY_VALUE,
            null,
            self::START_VALUE,
            self::LIMIT_VALUE,
            $suggestion
        );

        $this->assertEquals($expectedQuery, $query);
    }

    public function suggestionDataProvider(): array
    {
        return [
            [
                true,
                true,
            ],
            [
                1,
                true,
            ],
            [
                'true',
                true,
            ],
            [
                false,
                false,
            ],
            [
                0,
                false,
            ],
            [
                'false',
                false,
            ],
            [
                'something',
                false,
            ],
        ];
    }
}
