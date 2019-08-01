<?php

namespace CultuurNet\UDB3\Symfony\Proxy;

use GuzzleHttp\Client;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Hostname;
use ValueObjects\Web\PortNumber;

class CdbXmlProxyTest extends \PHPUnit_Framework_TestCase
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
        $response = $this->cdbXmlProxy->handle($this->request);

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
