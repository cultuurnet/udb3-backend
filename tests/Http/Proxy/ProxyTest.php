<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ProxyTest extends TestCase
{
    use AssertApiProblemTrait;
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
            new Hostname('search.uitdatabank.be'),
            new PortNumber(443),
            new Client()
        );

        $this->cdbXmlProxy = Proxy::createForCdbXml(
            'application/xml',
            new Hostname('cdbxml.uitdatabank.be'),
            new PortNumber(443),
            new Client()
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_valid_requests(): void
    {
        $searchRequest = $this->psr7RequestBuilder
            ->withUriFromString('https://search.uitdatabank.be/offers?apiKey=fa4e7657-fd68-4797-97f8-99daf6adf1a3')
            ->build('GET');

        $response = $this->searchProxy->handle($searchRequest);

        $this->assertTrue(is_subclass_of(get_class($response), ResponseInterface::class));
    }
}
