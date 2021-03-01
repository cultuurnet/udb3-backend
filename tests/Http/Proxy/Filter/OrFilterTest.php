<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class OrFilterTest extends TestCase
{
    public const APPLICATION_XML = 'application/xml';

    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $this->request = new Request(
            'GET',
            'http://www.foo.bar',
            [AcceptFilter::ACCEPT => self::APPLICATION_XML]
        );
    }

    /**
     * @test
     */
    public function it_does_match_when_one_filters_matches()
    {
        $orFilter = new OrFilter([
            new AcceptFilter(new StringLiteral(self::APPLICATION_XML)),
            new MethodFilter(new StringLiteral('POST')),
        ]);

        $this->assertTrue($orFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_no_filter_matches()
    {
        $orFilter = new OrFilter([
            new AcceptFilter(new StringLiteral('application/json')),
            new MethodFilter(new StringLiteral('PUT')),
        ]);

        $this->assertFalse($orFilter->matches($this->request));
    }
}
