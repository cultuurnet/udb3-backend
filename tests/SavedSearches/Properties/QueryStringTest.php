<?php

namespace CultuurNet\UDB3\SavedSearches\Properties;

use PHPUnit\Framework\TestCase;

class QueryStringTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_get_a_query_string_from_a_url_query_string()
    {
        // Valid URL query string, with a "q" parameter.
        $urlQueryString = 'a=b&q=city:leuven&c=d';
        $expected = 'city:leuven';

        $queryString = QueryString::fromURLQueryString($urlQueryString);

        $this->assertEquals($expected, $queryString);
        $this->assertEquals($expected, $queryString->toNative());

        // Valid URL query string, but without "q" parameter.
        $invalidUrlQueryString = 'a=b&c=d';
        $this->expectException(\InvalidArgumentException::class);
        QueryString::fromURLQueryString($invalidUrlQueryString);

        // Invalid URL query string.
        $invalidUrlQueryString = 'this is not a url query string';
        $this->expectException(\InvalidArgumentException::class);
        QueryString::fromURLQueryString($invalidUrlQueryString);
    }
}
