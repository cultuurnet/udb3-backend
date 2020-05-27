<?php

namespace CultuurNet\UDB3\Http;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class GuzzlePsr7FactoryTest extends TestCase
{
    /**
     * @var GuzzlePsr7Factory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new GuzzlePsr7Factory();
    }

    /**
     * @test
     * @dataProvider requestDataProvider
     *
     * @param string $method
     * @param UriInterface $uri
     * @param array $headers
     * @param string|null $body
     * @param string $protocolVersion
     */
    public function it_creates_psr7_requests_objects(
        $method,
        UriInterface $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        $request = $this->factory->createRequest(
            $method,
            $uri,
            $headers,
            $body,
            $protocolVersion
        );

        // We can't compare against an "expected" request because the bodies
        // would be two different stream objects, and the headers can contain
        // additional headers that weren't set explicitly.
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
        $this->assertEquals($body, $request->getBody()->getContents());
        $this->assertEquals($protocolVersion, $request->getProtocolVersion());
        foreach ($headers as $header => $value) {
            $this->assertEquals($value, $request->getHeaderLine($header));
        }
    }

    /**
     * @return array
     */
    public function requestDataProvider()
    {
        return [
            [
                'GET',
                new Uri('http://google.com'),
            ],
            [
                'POST',
                new Uri('https://foo.bar'),
                ['Content-Type' => 'application/json'],
                '{"foo":"bar"}',
                '5.0',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_can_create_psr7_uri_objects()
    {
        $url = 'http://foo.bar';

        $expected = new Uri($url);
        $actual = $this->factory->createUri($url);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_can_create_psr7_content_streams_from_strings()
    {
        $content = '{"foo":"bar"}';
        $stream = $this->factory->createContentStream($content);

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals($content, $stream->getContents());
    }
}
