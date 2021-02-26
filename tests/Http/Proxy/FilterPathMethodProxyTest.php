<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Hostname;
use ValueObjects\Web\PortNumber;

class FilterPathMethodProxyTest extends TestCase
{
    /**
     * @var FilterPathMethodProxy
     */
    private $proxy;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    public function setUp()
    {
        $this->client = $this->createMock(ClientInterface::class);

        $this->proxy = new FilterPathMethodProxy(
            new FilterPathRegex('^\/event\/(?<offerId>[a-zA-Z0-9\-]+)\/calendar-summary$'),
            new StringLiteral('GET'),
            new Hostname('www.google.be'),
            new PortNumber(80),
            new DiactorosFactory(),
            new HttpFoundationFactory(),
            $this->client
        );

        $this->request = Request::create(
            'http://www.2dotstwice.be:666/event/4cb7f311-11cd-486e-88d6-c242489ac235/calendar-summary',
            'GET'
        );
        $this->request->headers->set('Accept', 'text/html');
    }

    /**
     * @test
     */
    public function it_should_handle_requests_that_match_the_configured_path_and_method()
    {
        $this
          ->client
          ->expects($this->once())
          ->method('send')
          ->willReturn(new Response());

        $response = $this->proxy->handle($this->request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_should_not_handle_requests_that_do_not_match_the_configured_path()
    {
        $request = Request::create(
            'http://www.2dotstwice.be:666/event/4cb7f311-11cd-486e-88d6-c242489ac235/calendar-shmummary',
            'GET'
        );
        $response = $this->proxy->handle($request);

        $this->assertNull($response);
    }

    /**
     * @test
     */
    public function it_should_not_handle_requests_that_do_not_match_the_configured_method()
    {
        $this->request->setMethod('POST');
        $response = $this->proxy->handle($this->request);

        $this->assertNull($response);
    }

    /**
     * @test
     */
    public function is_should_not_modify_original_request()
    {
        $request = Request::create(
            'http://www.2dotstwice.be',
            'GET',
            [],
            [],
            [],
            [],
            '{"label":"demo"}'
        );
        $expectedRequest = $request->duplicate();

        $this->proxy->handle($request);

        $this->assertEquals($expectedRequest, $request);
    }
}
