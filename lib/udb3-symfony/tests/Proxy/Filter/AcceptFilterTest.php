<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

class AcceptFilterTest extends TestCase
{
    const APPLICATION_XML = 'application/xml';

    /**
     * @var RequestInterface
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
    public function it_does_match_same_accept_header()
    {
        $acceptFilter = new AcceptFilter(
            new StringLiteral(self::APPLICATION_XML)
        );

        $this->assertTrue($acceptFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_for_different_accept_header()
    {
        $acceptFilter = new AcceptFilter(
            new StringLiteral("application/xmls")
        );

        $this->assertFalse($acceptFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_accept_header_is_missing()
    {
        $request = $this->request->withoutHeader(AcceptFilter::ACCEPT);

        $acceptFilter = new AcceptFilter(
            new StringLiteral(self::APPLICATION_XML)
        );

        $this->assertFalse($acceptFilter->matches($request));
    }
}
