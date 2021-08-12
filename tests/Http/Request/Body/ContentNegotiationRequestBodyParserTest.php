<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

class ContentNegotiationRequestBodyParserTest extends TestCase
{
    private ContentNegotiationRequestBodyParser $parser;
    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->parser = new ContentNegotiationRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_parses_content_type_application_json_as_json(): void
    {
        $given = $this->requestBuilder
            ->withHeader('content-type', 'application/json')
            ->withBodyFromString('{"foo":"bar"}')
            ->build('PUT');

        $expected = (object) ['foo' => 'bar'];
        $actual = $this->parser->parse($given);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_parses_unknown_content_types_as_json(): void
    {
        $given = $this->requestBuilder
            ->withHeader('content-type', 'blabla')
            ->withBodyFromString('{"foo":"bar"}')
            ->build('PUT');

        $expected = (object) ['foo' => 'bar'];
        $actual = $this->parser->parse($given);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_parses_missing_content_types_as_json(): void
    {
        $given = $this->requestBuilder
            ->withBodyFromString('{"foo":"bar"}')
            ->build('PUT');

        $expected = (object) ['foo' => 'bar'];
        $actual = $this->parser->parse($given);

        $this->assertEquals($expected, $actual);
    }
}
