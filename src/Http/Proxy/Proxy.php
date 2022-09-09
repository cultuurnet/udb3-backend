<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\Proxy\Filter\FilterInterface;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\CombinedReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\DomainReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\PortReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\RequestTransformerInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use GuzzleHttp\ClientInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class Proxy
{
    /**
     * @var FilterInterface
     */
    private $filter;

    /**
     * @var RequestTransformerInterface
     */
    private $requestTransformer;

    /**
     * @var DiactorosFactory
     */
    private $diactorosFactory;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Proxy constructor.
     */
    public function __construct(
        FilterInterface $filter,
        Hostname $hostname,
        PortNumber $port,
        DiactorosFactory $diactorosFactory,
        HttpFoundationFactory $httpFoundationFactory,
        ClientInterface $client
    ) {
        $this->filter = $filter;
        $this->requestTransformer = $this->createTransformer($hostname, $port);
        $this->diactorosFactory = $diactorosFactory;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->client = $client;
    }

    /**
     * @return null|Response
     */
    public function handle(Request $request)
    {
        $response = null;

        $psr7Request = $this->diactorosFactory->createRequest(
            $request->duplicate()
        );

        if ($this->filter->matches($psr7Request)) {
            // Transform the request before re-sending it so we don't send the
            // exact same request and end up in an infinite loop.
            $psr7Request = $this->requestTransformer->transform($psr7Request);

            $psr7Response = $this->client->send(
                $psr7Request,
                [
                    'http_errors' => false,
                ]
            );
            $response = $this->httpFoundationFactory->createResponse($psr7Response);

            // Without removing the transfer encoding the consuming client would
            // get an error. (Both curl and Postman)
            $response->headers->remove('Transfer-Encoding');
        }

        return $response;
    }

    private function createTransformer(
        Hostname $hostname,
        PortNumber $port
    ): CombinedReplacer {
        $domainReplacer = new DomainReplacer($hostname);

        $portReplacer = new PortReplacer($port);

        return new CombinedReplacer([$domainReplacer, $portReplacer]);
    }
}
