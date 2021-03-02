<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\RequestMatcher;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PreflightRequestMatcherTest extends TestCase
{
    /**
     * @test
     */
    public function it_matches_preflight_requests()
    {
        $request = Request::create('/foo', 'OPTIONS');
        $request->headers->set('Access-Control-Request-Method', 'POST');

        $matcher = new PreflightRequestMatcher();
        $this->assertTrue($matcher->matches($request));
    }

    /**
     * @test
     */
    public function it_does_not_match_preflight_requests_without_request_method()
    {
        $request = Request::create('/foo', 'OPTIONS');
        $matcher = new PreflightRequestMatcher();
        $this->assertFalse($matcher->matches($request));
    }

    /**
     * @test
     * @dataProvider unmatchedRequestMethods
     */
    public function it_does_not_match_request_methods_other_then_OPTIONS(string $requestMethod)
    {
        $request = Request::create('/foo', $requestMethod);
        $request->headers->set('Access-Control-Request-Method', $requestMethod);

        $matcher = new PreflightRequestMatcher();
        $this->assertFalse($matcher->matches($request));
    }

    /**
     * It's hard to test against a whitelist but these are all the methods for which Silex provides controllers
     * @return string[][]
     */
    public function unmatchedRequestMethods(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['PATCH'],
        ];
    }
}
