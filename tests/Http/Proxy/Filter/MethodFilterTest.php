<?php

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class MethodFilterTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $this->request = new Request(
            'GET',
            'http://www.foo.bar'
        );
    }

    /**
     * @test
     */
    public function it_does_match_the_same_http_method()
    {
        $methodFilter = new MethodFilter(new StringLiteral('GET'));

        $this->assertTrue($methodFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_for_a_different_http_method()
    {
        $methodFilter = new MethodFilter(new StringLiteral('POST'));

        $this->assertFalse($methodFilter->matches($this->request));
    }
}
