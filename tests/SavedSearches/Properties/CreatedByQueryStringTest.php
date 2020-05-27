<?php

namespace CultuurNet\UDB3\SavedSearches\Properties;

use PHPUnit\Framework\TestCase;

class CreateByQueryStringTest extends TestCase
{
    /**
     * @test
     * @dataProvider createdByQueryDataProvider
     * @param CreatedByQueryString $createdByQueryString
     * @param string $expectedQuery
     */
    public function it_can_create_query_strings(
        CreatedByQueryString $createdByQueryString,
        string $expectedQuery
    ): void {
        $this->assertEquals(
            $createdByQueryString->toNative(),
            $expectedQuery
        );
    }

    /**
     * @return array
     */
    public function createdByQueryDataProvider(): array
    {
        $userId = 'cef70b98-2d4d-40a9-95f0-762aae66ef3f';
        $emailAddress = 'foo@bar.com';

        return [
            [
                new CreatedByQueryString($userId),
                'createdby:' . $userId,
            ],
            [
                new CreatedByQueryString($emailAddress),
                'createdby:' . $emailAddress,
            ],
            [
                new CreatedByQueryString($userId, $emailAddress),
                'createdby:' . '(' . $userId . ' OR ' . $emailAddress . ')',
            ],
        ];
    }
}
