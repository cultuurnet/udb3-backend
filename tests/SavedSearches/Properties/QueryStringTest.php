<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Properties;

use PHPUnit\Framework\TestCase;

class QueryStringTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_get_a_query_string_from_a_url_query_string(): void
    {
        // Valid URL query string, with a "q" parameter.
        $urlQueryString = 'a=b&q=city:leuven&c=d';
        $expected = 'city:leuven';

        $queryString = QueryString::fromURLQueryString($urlQueryString);

        $this->assertEquals($expected, $queryString->toString());

        // Valid URL query string, but without "q" parameter.
        $invalidUrlQueryString = 'a=b&c=d';
        $this->expectException(\InvalidArgumentException::class);
        QueryString::fromURLQueryString($invalidUrlQueryString);

        // Invalid URL query string.
        $invalidUrlQueryString = 'this is not a url query string';
        $this->expectException(\InvalidArgumentException::class);
        QueryString::fromURLQueryString($invalidUrlQueryString);
    }


    /**
     * @test
     * @dataProvider dataproviderBrokenQueries
     */
    public function it_cleans_broken_queries(string $brokenQuery, string $fixedQuery): void
    {
        $this->assertEquals($fixedQuery, (new QueryString($brokenQuery))->toString());
    }

    public function dataproviderBrokenQueries(): array
    {
        return [
            ['test\*[\:test\:]', 'test\*[:test:]'],
            ['%2B', '+'],
            [
                'address.*.addressLocality:Scherpenheuvel-Zichem AND dateRange:[2015-05-31T22\:00\:00%2B00\:00 TO 2015-07-31T21\:59\:59%2B00\:00]',
                'address.*.addressLocality:Scherpenheuvel-Zichem AND dateRange:[2015-05-31T22:00:00+00:00 TO 2015-07-31T21:59:59+00:00]',
            ],
            ['name.\*:wijndegustatie% AND address.\*.addressLocality:lede', 'name.\*:wijndegustatie% AND address.\*.addressLocality:lede'],
            ['address.\*.postalCode:3090 AND (dateRange:[2018-08-31T22\:00\:00%2B00\:00 TO 2018-10-31T22\:59\:59%2B00\:00] AND (dateRange:[2018-08-31T22\:00\:00%2B00\:00 TO 2018-10-31T22\:59\:59%2B00\:00] AND !(calendarType:permanent)))', 'address.\*.postalCode:3090 AND (dateRange:[2018-08-31T22:00:00+00:00 TO 2018-10-31T22:59:59+00:00] AND (dateRange:[2018-08-31T22:00:00+00:00 TO 2018-10-31T22:59:59+00:00] AND !(calendarType:permanent)))'],
            ['address.\*.addressLocality:Zandhoven AND dateRange:[2016-10-31T23\:00\:00%2B00\:00 TO 2016-12-31T22\:59\:59%2B00\:00]', 'address.\*.addressLocality:Zandhoven AND dateRange:[2016-10-31T23:00:00+00:00 TO 2016-12-31T22:59:59+00:00]'],
            ['address.\*.addressLocality:sint-martens-latem AND dateRange:[2015-11-10T23\:00\:00%2B00\:00 TO *]', 'address.\*.addressLocality:sint-martens-latem AND dateRange:[2015-11-10T23:00:00+00:00 TO *]'],
        ];
    }
}
