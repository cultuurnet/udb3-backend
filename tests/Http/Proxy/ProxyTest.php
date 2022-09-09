<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\Proxy\Filter\FilterInterface;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\RequestTransformerInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
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

        $this->client = $this->createMock(ClientInterface::class);

        $this->proxy = new Proxy(
            $this->filter,
            new Hostname('foo.bar'),
            new PortNumber(443),
            $this->client
        );
    }

    /**
     * @test
     */
    public function it_filters_out_requests_that_do_not_accept_xml()
    {
        $sfRequest = SymfonyRequest::create('https://foo.bar', 'GET');
        $psr7Request = new Psr7Request('GET', 'https://foo.bar');

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
        $sfRequest = SymfonyRequest::create('https://foo.bar', 'GET');
        $psr7Request = new Psr7Request('GET', 'https://foo.bar');
        $transformedPsr7Request = $psr7Request->withUri(new Uri('https://foo.bar'));

        $psr7Response = new Psr7Response(200, [], 'All good.');
        $sfResponse = new SymfonyResponse('All good.', 200, []);

        $this->filter->expects($this->once())
            ->method('matches')
            ->with($psr7Request)
            ->willReturn(true);

        $this->client->expects($this->once())
            ->method('send')
            ->with($transformedPsr7Request)
            ->willReturn($psr7Response);

        $this->assertEquals(
            $sfResponse,
            $this->proxy->handle($sfRequest)
        );
    }
}
