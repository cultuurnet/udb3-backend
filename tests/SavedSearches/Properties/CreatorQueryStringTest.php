<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Properties;

use PHPUnit\Framework\TestCase;

class CreatorQueryStringTest extends TestCase
{
    /**
     * @test
     * @dataProvider creatorQueryDataProvider
     */
    public function it_can_create_query_strings(
        CreatorQueryString $creatorQueryString,
        string $expectedQuery
    ): void {
        $this->assertEquals(
            $creatorQueryString->toString(),
            $expectedQuery
        );
    }


    public function creatorQueryDataProvider(): array
    {
        $userId = 'cef70b98-2d4d-40a9-95f0-762aae66ef3f';
        $emailAddress = 'foo@bar.com';

        return [
            [
                new CreatorQueryString($userId),
                'creator:' . $userId,
            ],
            [
                new CreatorQueryString($emailAddress),
                'creator:' . $emailAddress,
            ],
            [
                new CreatorQueryString($userId, $emailAddress),
                'creator:' . '(' . $userId . ' OR ' . $emailAddress . ')',
            ],
        ];
    }
}
