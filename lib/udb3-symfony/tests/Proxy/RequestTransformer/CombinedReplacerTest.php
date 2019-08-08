<?php

namespace CultuurNet\UDB3\Symfony\Proxy\RequestTransformer;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Hostname;
use ValueObjects\Web\PortNumber;

class CombinedReplacerTest extends TestCase
{
    const ORIGINAL_DOMAIN = 'www.original.be';
    const REPLACED_DOMAIN = 'www.replaced.be';

    const ORIGINAL_PORT = 80;
    const REPLACED_PORT = 666;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var CombinedReplacer
     */
    private $combinedReplacer;

    public function setUp()
    {
        $this->request = new Request(
            'GET',
            'http://' . self::ORIGINAL_DOMAIN . ':' . self::ORIGINAL_PORT
        );

        $domainReplacer = new DomainReplacer(
            new Hostname(self::REPLACED_DOMAIN)
        );

        $portReplacer = new PortReplacer(
            new PortNumber(self::REPLACED_PORT)
        );

        $this->combinedReplacer = new CombinedReplacer(
            [$domainReplacer, $portReplacer]
        );
    }

    /**
     * @test
     */
    public function it_combines_all_transformations()
    {
        $transformedRequest = $this->combinedReplacer->transform($this->request);

        $this->assertEquals(
            self::REPLACED_DOMAIN,
            $transformedRequest->getUri()->getHost()
        );

        $this->assertEquals(
            self::REPLACED_PORT,
            $transformedRequest->getUri()->getPort()
        );
    }
}
