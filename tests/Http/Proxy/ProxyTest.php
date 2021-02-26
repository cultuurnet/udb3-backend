<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\Proxy\Filter\FilterInterface;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\RequestTransformerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProxyTest extends TestCase
{
    /**
     * @var FilterInterface|MockObject
     */
    private $filter;

    /**
     * @var RequestTransformerInterface|MockObject
     */
    private $requestTransformer;

    /**
     * @var DiactorosFactory|MockObject
     */
    private $diactorosFactory;

    /**
     * @var HttpFoundationFactory|MockObject
     */
    private $httpFoundationFactory;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    /**
     * @var Proxy
     */
    private $proxy;

    public function setUp()
    {
        $this->filter = $this->createMock(FilterInterface::class);
        $this->requestTransformer = $this->createMock(RequestTransformerInterface::class);

        $this->diactorosFactory = $this->createMock(DiactorosFactory::class);
        $this->httpFoundationFactory = $this->createMock(HttpFoundationFactory::class);

        $this->client = $this->createMock(ClientInterface::class);

        $this->proxy = new Proxy(
            $this->filter,
            $this->requestTransformer,
            $this->diactorosFactory,
            $this->httpFoundationFactory,
            $this->client
        );
    }

    /**
     * @test
     */
    public function it_filters_out_requests_that_do_not_accept_xml()
    {
        $sfRequest = SymfonyRequest::create('http://foo.bar', 'GET');
        $psr7Request = new Psr7Request('GET', 'http://foo.bar');

        $this->diactorosFactory->expects($this->once())
            ->method('createRequest')
            ->with($sfRequest)
            ->willReturn($psr7Request);

        $this->filter->expects($this->once())
            ->method('matches')
            ->with($psr7Request)
            ->willReturn(false);

        $this->requestTransformer->expects($this->never())
            ->method('transform');

        $this->client->expects($this->never())
            ->method('send');

        $this->assertNull(
            $this->proxy->handle($sfRequest)
        );
    }

    /**
     * @test
     */
    public function it_sends_incoming_requests_after_transforming_them_and_returns_their_response()
    {
        $sfRequest = SymfonyRequest::create('http://foo.bar', 'GET');
        $psr7Request = new Psr7Request('GET', 'http://foo.bar');
        $transformedPsr7Request = $psr7Request->withUri(new Uri('http://foo.baz'));

        $psr7Response = new Psr7Response(200, [], 'All good.');
        $sfResponse = new SymfonyResponse('All good.', 200, []);

        $this->diactorosFactory->expects($this->once())
            ->method('createRequest')
            ->with($sfRequest)
            ->willReturn($psr7Request);

        $this->filter->expects($this->once())
            ->method('matches')
            ->with($psr7Request)
            ->willReturn(true);

        $this->requestTransformer->expects($this->once())
            ->method('transform')
            ->with($psr7Request)
            ->willReturn($transformedPsr7Request);

        $this->client->expects($this->once())
            ->method('send')
            ->with($transformedPsr7Request)
            ->willReturn($psr7Response);

        $this->httpFoundationFactory->expects($this->once())
            ->method('createResponse')
            ->with($psr7Response)
            ->willReturn($sfResponse);

        $this->assertEquals(
            $sfResponse,
            $this->proxy->handle($sfRequest)
        );
    }
}
