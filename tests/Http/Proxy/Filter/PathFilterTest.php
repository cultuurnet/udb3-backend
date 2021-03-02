<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use CultuurNet\UDB3\Http\Proxy\FilterPathRegex;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class PathFilterTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_filter_requests_that_match_the_path_regex()
    {
        $request = new Request('GET', 'http://www.foo.bar/beep/boop');
        $filter = new PathFilter(new FilterPathRegex('^\/beep\/boop'));

        $this->assertTrue($filter->matches($request));
    }

    /**
     * @test
     */
    public function it_should_not_filter_requests_that_do_not_match_the_path_regex()
    {
        $request = new Request('GET', 'http://www.foo.bar/bleep/bloop');
        $filter = new PathFilter(new FilterPathRegex('^\/beep\/boop'));
        $this->assertFalse($filter->matches($request));
    }

    /**
     * @test
     */
    public function it_should_not_filter_requests_that_have_no_path()
    {
        $request = new Request('GET', 'http://www.foo.bar');
        $filter = new PathFilter(new FilterPathRegex('^\/beep\/boop'));
        $this->assertFalse($filter->matches($request));
    }
}
