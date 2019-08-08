<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use CultuurNet\UDB3\Symfony\Proxy\FilterPathRegex;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class PreflightFilterTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $this->request = new Request(
            'OPTIONS',
            'http://www.foo.bar/beep/boop',
            ['Access-Control-Request-Method'=> 'POST']
        );
    }

    /**
     * @test
     */
    public function it_does_match_options_call_with_same_request_method()
    {
        $preflightFilter = new PreflightFilter(
            new FilterPathRegex('^\/beep\/boop'),
            new StringLiteral('POST')
        );

        $this->assertTrue($preflightFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match()
    {
        $preflightFilter = new PreflightFilter(
            new FilterPathRegex('^\/beep\/boop'),
            new StringLiteral('GET')
        );

        $this->assertFalse($preflightFilter->matches($this->request));
    }
}
