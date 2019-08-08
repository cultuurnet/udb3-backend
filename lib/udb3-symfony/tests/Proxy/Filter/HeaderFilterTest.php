<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class HeaderFilterTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $this->request = new Request(
            'POST',
            'http://www.foo.bar',
            ['Access-Control-Request-Method'=> 'POST']
        );
    }

    /**
     * @test
     */
    public function it_does_match_same_header()
    {
        $headerFilter = new HeaderFilter(
            new StringLiteral('Access-Control-Request-Method'),
            new StringLiteral('POST')
        );

        $this->assertTrue($headerFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_header_value_different()
    {
        $headerFilter = new HeaderFilter(
            new StringLiteral('Access-Control-Request-Method'),
            new StringLiteral('GET')
        );

        $this->assertFalse($headerFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_header_missing()
    {
        $headerFilter = new HeaderFilter(
            new StringLiteral('Content-Type'),
            new StringLiteral('POST')
        );

        $this->assertFalse($headerFilter->matches($this->request));
    }
}
