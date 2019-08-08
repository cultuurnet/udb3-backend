<?php

namespace CultuurNet\UDB3\Http\Proxy;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Hostname;
use ValueObjects\Web\PortNumber;
use Zend\Diactoros\Uri;

class CdbXmlProxyTest extends TestCase
{
    const APPLICATION_XML = 'application/xml';

    /**
     * @var CdbXmlProxy
     */
    private $cdbXmlProxy;

    /**
     * @var Request
     */
    private $request;

    public function setUp()
    {
        $this->cdbXmlProxy = new CdbXmlProxy(
            new StringLiteral(self::APPLICATION_XML),
            new Hostname('www.google.be'),
            new PortNumber(80),
            new DiactorosFactory(),
            new HttpFoundationFactory(),
            new Client()
        );

        $this->request = Request::create(
            'http://www.2dotstwice.be:666',
            'GET'
        );
        $this->request->headers->set('Accept', self::APPLICATION_XML);
    }

    /**
     * @test
     */
    public function it_handles_requests_with_given_accept_and_get_method()
    {
        $handler = new MockHandler([new Response(200)]);
        $client = new Client(['handler' => $handler]);
        $cdbXmlProxy = new CdbXmlProxy(
            new StringLiteral(self::APPLICATION_XML),
            new Hostname('www.google.be'),
            new PortNumber(80),
            new DiactorosFactory(),
            new HttpFoundationFactory(),
            $client
        );
        $response = $cdbXmlProxy->handle($this->request);
        $lastRequest = $handler->getLastRequest();
        $this->assertEquals(new Uri('http://www.google.be:80/'), $lastRequest->getUri());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_filters_requests_with_wrong_accept()
    {
        $this->request->headers->set('Accept', 'application/json');
        $response = $this->cdbXmlProxy->handle($this->request);

        $this->assertNull($response);
    }

    /**
     * @test
     */
    public function it_filters_requests_with_wrong_method()
    {
        $this->request->setMethod('POST');
        $response = $this->cdbXmlProxy->handle($this->request);

        $this->assertNull($response);
    }

    /**
     * @test
     */
    public function is_does_not_modify_original_request()
    {
        $request = Request::create(
            'http://www.2dotstwice.be',
            'POST',
            [],
            [],
            [],
            [],
            '{"label":"demo"}'
        );
        $expectedRequest = $request->duplicate();

        $this->cdbXmlProxy->handle($request);

        $this->assertEquals($expectedRequest, $request);
    }
}
