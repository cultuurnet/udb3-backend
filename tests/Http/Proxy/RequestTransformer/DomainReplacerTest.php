<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

use CultuurNet\UDB3\Http\Proxy\Filter\AcceptFilter;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ValueObjects\Web\Hostname;

class DomainReplacerTest extends TestCase
{
    public const ORIGINAL_DOMAIN = 'www.original.be';
    public const REPLACED_DOMAIN = 'www.replaced.be';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var DomainReplacer
     */
    private $domainReplacer;

    protected function setUp()
    {
        $this->request = new Request(
            'GET',
            'http://' . self::ORIGINAL_DOMAIN,
            [AcceptFilter::ACCEPT => 'application/xml']
        );

        $this->domainReplacer = new DomainReplacer(
            new Hostname(self::REPLACED_DOMAIN)
        );
    }

    /**
     * @test
     */
    public function it_replaces_the_domain_of_a_request()
    {
        /** @var RequestInterface $transformedRequest */
        $transformedRequest = $this->domainReplacer->transform($this->request);

        $this->assertEquals(
            self::REPLACED_DOMAIN,
            $transformedRequest->getUri()->getHost()
        );
    }
}
