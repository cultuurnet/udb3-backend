<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class PortReplacerTest extends TestCase
{
    public const ORIGINAL_PORT = '8080';
    public const REPLACED_PORT = '666';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var PortReplacer
     */
    private $portReplacer;

    protected function setUp()
    {
        $this->request = new Request(
            'GET',
            'http://www.url.com:' . self::ORIGINAL_PORT
        );

        $this->portReplacer = new PortReplacer(
            new PortNumber((int) self::REPLACED_PORT)
        );
    }

    /**
     * @test
     */
    public function it_replaces_the_port_of_a_request()
    {
        $transformedRequest = $this->portReplacer->transform($this->request);

        $this->assertEquals(
            self::REPLACED_PORT,
            $transformedRequest->getUri()->getPort()
        );
    }

    /**
     * @test
     */
    public function it_removes_port_of_a_request_if_80()
    {
        $portReplacer = new PortReplacer(
            new PortNumber(80)
        );

        $transformedRequest = $portReplacer->transform($this->request);

        $this->assertEquals(
            null,
            $transformedRequest->getUri()->getPort()
        );
    }
}
