<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use GuzzleHttp\Psr7\Request;
use ValueObjects\StringLiteral\StringLiteral;

class AndFilterTest extends \PHPUnit_Framework_TestCase
{
    const APPLICATION_XML = 'application/xml';

    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $this->request = new Request(
            'POST',
            'http://www.foo.bar',
            [AcceptFilter::ACCEPT => self::APPLICATION_XML]
        );
    }

    /**
     * @test
     */
    public function it_does_match_when_all_filters_match()
    {
        $andFilter = new AndFilter(array(
            new AcceptFilter(new StringLiteral(self::APPLICATION_XML)),
            new MethodFilter(new StringLiteral('POST'))
        ));

        $this->assertTrue($andFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_at_least_one_filter_does_not_match()
    {
        $andFilter = new AndFilter(array(
            new AcceptFilter(new StringLiteral(self::APPLICATION_XML)),
            new MethodFilter(new StringLiteral('PUT'))
        ));

        $this->assertFalse($andFilter->matches($this->request));
    }
}
