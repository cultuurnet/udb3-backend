<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ProxyRequestHandlerTest extends TestCase
{
    private ProxyRequestHandler $proxyRequestHandler;
    private MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->proxyRequestHandler = new ProxyRequestHandler('newdomain.local', $this->httpClient);
    }

    /**
     * @test
     */
    public function it_changes_the_domain_and_then_resends_it_and_returns_the_response(): void
    {
        $originalRequest = (new Psr7RequestBuilder())
            ->withUriFromString('http://mock.local:80/test/foo/bar')
            ->withJsonBodyFromArray(['foo' => 'bar'])
            ->withHeader('x-test-header', 'example value')
            ->build('POST');

        $expectedProxyRequest = (new Psr7RequestBuilder())
            ->withUriFromString('https://newdomain.local/test/foo/bar')
            ->withJsonBodyFromArray(['foo' => 'bar'])
            ->withHeader('x-test-header', 'example value')
            ->build('POST');

        $expectedOptions = ['http_errors' => false];

        $expectedResponse = new NoContentResponse();

        $actualProxyRequest = null;
        $actualOptions = [];

        // Don't do a direct comparison of the $expectedProxyRequest and the actual request, because the resource id
        // of the body stream will differ.
        $this->httpClient->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (RequestInterface $request, array $options) use (&$actualProxyRequest, &$actualOptions, $expectedResponse) {
                $actualProxyRequest = $request;
                $actualOptions = $options;
                return $expectedResponse;
            });

        $actualResponse = $this->proxyRequestHandler->handle($originalRequest);

        $this->assertEquals($expectedProxyRequest->getUri(), $actualProxyRequest->getUri());
        $this->assertEquals($expectedProxyRequest->getMethod(), $actualProxyRequest->getMethod());
        $this->assertEquals($expectedProxyRequest->getBody()->getContents(), $actualProxyRequest->getBody()->getContents());
        $this->assertEquals($expectedProxyRequest->getHeaders(), $actualProxyRequest->getHeaders());
        $this->assertEquals($expectedOptions, $actualOptions);
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
