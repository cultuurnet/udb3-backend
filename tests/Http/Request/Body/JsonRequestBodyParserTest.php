<?php

namespace CultuurNet\UDB3\Http\Request\Body;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class JsonRequestBodyParserTest extends TestCase
{
    /**
     * @var JsonRequestBodyParser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new JsonRequestBodyParser();
    }

    /**
     * @test
     */
    public function it_returns_the_decoded_json_body_as_an_associative_array(): void
    {
        $given = $this->createMockRequestWithBody('{"foo":"bar"}');
        $expected = ['foo' => 'bar'];
        $actual = $this->parser->parse($given);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_when_body_is_missing(): void
    {
        $given = $this->createMockRequestWithBody('');
        $this->expectException(RequestBodyMissing::class);
        $this->parser->parse($given);
    }

    /**
     * @test
     */
    public function it_throws_when_body_is_invalid_json(): void
    {
        $given = $this->createMockRequestWithBody('{{}');
        $this->expectException(RequestBodyInvalidSyntax::class);
        $this->parser->parse($given);
    }

    private function createMockRequestWithBody(string $body): ServerRequestInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')
            ->willReturn($body);

        $mock = $this->createMock(ServerRequestInterface::class);
        $mock->method('getBody')
            ->willReturn($stream);

        /** @var ServerRequestInterface $mock */
        return $mock;
    }
}
