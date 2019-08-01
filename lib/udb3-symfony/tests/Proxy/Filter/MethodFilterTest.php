<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use GuzzleHttp\Psr7\Request;
use ValueObjects\StringLiteral\StringLiteral;

class MethodFilterTest extends \PHPUnit_Framework_TestCase
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
