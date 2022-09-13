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

    /**
     * @test
     */
    public function it_throws_on_requests_with_invalid_accept(): void
    {
        $cdbXmlRequest = $this->psr7RequestBuilder
            ->withHeader('Accept', 'application/json')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::notAcceptable(),
            fn () => $this->cdbXmlProxy->handle($cdbXmlRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_requests_with_invalid_method(): void
    {
        $searchRequest = $this->psr7RequestBuilder
            ->withUriFromString('https://search.uitdatabank.be/offers?apiKey=fa4e7657-fd68-4797-97f8-99daf6adf1a3')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::methodNotAllowed(),
            fn () => $this->searchProxy->handle($searchRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_requests_with_invalid_path(): void
    {
        $searchRequest = $this->psr7RequestBuilder
            ->withUriFromString('https://search.foo.bar/plaatsen?apiKey=fa4e7657-fd68-4797-97f8-99daf6adf1a3')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound(),
            fn () => $this->searchProxy->handle($searchRequest)
        );
    }
}
