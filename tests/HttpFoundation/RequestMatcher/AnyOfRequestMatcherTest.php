<?php

namespace CultuurNet\UDB3\HttpFoundation\RequestMatcher;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;

class AnyOfRequestMatcherTest extends TestCase
{
    /**
     * @test
     */
    public function it_matches_no_request_if_no_request_matchers_have_been_injected()
    {
        $anyOf = new AnyOfRequestMatcher();
        $request = new Request();
        $this->assertFalse($anyOf->matches($request));
    }

    /**
     * @test
     * @dataProvider matchingRequestDataProvider
     *
     * @param bool $shouldMatch
     */
    public function it_matches_if_the_request_is_matched_by_any_of_the_injected_request_matchers(
        Request $request,
        $shouldMatch
    ) {
        $shouldMatch = (bool) $shouldMatch;

        $anyOf = (new AnyOfRequestMatcher())
            ->with(new RequestMatcher('foo/bar', null, 'GET'))
            ->with(new RequestMatcher('lorem/ipsum', null, ['PUT', 'DELETE']));

        $this->assertEquals($shouldMatch, $anyOf->matches($request));
    }

    /**
     * @return array
     */
    public function matchingRequestDataProvider()
    {
        return [
            'it should match if both the uri and method match' => [
                'request' => Request::create('foo/bar', Request::METHOD_GET),
                'should match' => true,
            ],
            'it should not match if only the uri matches' => [
                'request' => Request::create('foo/bar', Request::METHOD_POST),
                'should match' => false,
            ],
            'it should match if the uri matches and the method matches one of many' => [
                'request' => Request::create('lorem/ipsum', Request::METHOD_PUT),
                'should match' => true,
            ],
            'it should not match if the uri matches but the method does not match one of many' => [
                'request' => Request::create('lorem/ipsum', Request::METHOD_GET),
                'should match' => false,
            ],
        ];
    }
}
