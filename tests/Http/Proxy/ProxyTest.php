<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

final class ProxyTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private Proxy $searchProxy;

    private Proxy $cdbXmlProxy;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->searchProxy = Proxy::createForSearch(
            new FilterPathRegex('^\/(events|places|organizers|offers)\/?$'),
            'GET',
            new Hostname('search.foo.bar'),
            new PortNumber(443),
            new Client()
        );

        $this->cdbXmlProxy = Proxy::createForCdbXml(
            'application/xml',
            new Hostname('cdbxml.foo.bar'),
            new PortNumber(443),
            new Client()
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }
}
