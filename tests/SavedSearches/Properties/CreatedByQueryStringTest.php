<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Properties;

use PHPUnit\Framework\TestCase;

class CreatedByQueryStringTest extends TestCase
{
    /**
     * @test
     * @dataProvider createdByQueryDataProvider
     */
    public function it_can_create_query_strings(
        CreatedByQueryString $createdByQueryString,
        string $expectedQuery
    ): void {
        $this->assertEquals(
            $createdByQueryString->toString(),
            $expectedQuery
        );
    }


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
