<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use GuzzleHttp\Psr7\Request;
use ValueObjects\StringLiteral\StringLiteral;

class ContentTypeFilterTest extends \PHPUnit_Framework_TestCase
{
    const APPLICATION_XML = 'application/xml';

    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $this->request = new Request(
            'GET',
            'http://www.foo.bar',
            [ContentTypeFilter::CONTENT_TYPE => self::APPLICATION_XML]
        );
    }

    /**
     * @test
     */
    public function it_does_match_same_content_type()
    {
        $contentTypeFilter = new ContentTypeFilter(
            new StringLiteral(self::APPLICATION_XML)
        );

        $this->assertTrue($contentTypeFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_for_different_content_type()
    {
        $contentTypeFilter = new ContentTypeFilter(
            new StringLiteral("application/xmls")
        );

        $this->assertFalse($contentTypeFilter->matches($this->request));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_content_type_is_missing()
    {
        $request = $this->request->withoutHeader(ContentTypeFilter::CONTENT_TYPE);

        $contentTypeFilter = new ContentTypeFilter(
            new StringLiteral(self::APPLICATION_XML)
        );

        $this->assertFalse($contentTypeFilter->matches($request));
    }
}
