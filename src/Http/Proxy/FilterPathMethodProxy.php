<?php

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\Proxy\Filter\AndFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\FilterInterface;
use CultuurNet\UDB3\Http\Proxy\Filter\MethodFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\OrFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\PathFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\PreflightFilter;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\CombinedReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\DomainReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\PortReplacer;
use GuzzleHttp\ClientInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Domain;
use ValueObjects\Web\PortNumber;

class FilterPathMethodProxy extends Proxy
{
    /**
     * CdbXmlProxy constructor.
     * @param FilterPathRegex $path
     * @param StringLiteral|null $method
     * @param Domain $domain
     * @param PortNumber $port
     * @param DiactorosFactory $diactorosFactory
     * @param HttpFoundationFactory $httpFoundationFactory
     * @param ClientInterface $client
     */
    public function __construct(
        FilterPathRegex $path,
        StringLiteral $method,
        Domain $domain,
        PortNumber $port,
        DiactorosFactory $diactorosFactory,
        HttpFoundationFactory $httpFoundationFactory,
        ClientInterface $client
    ) {
        parent::__construct(
            $this->createFilter($path, $method),
            $this->createTransformer($domain, $port),
            $diactorosFactory,
            $httpFoundationFactory,
            $client
        );
    }

    /**
     * @param FilterPathRegex $path
     * @param StringLiteral $method
     * @return FilterInterface
     */
    private function createFilter(FilterPathRegex $path, StringLiteral $method)
    {
        $pathMethodFilter = new AndFilter(
            [
                new PathFilter($path),
                new MethodFilter($method),
            ]
        );

        return new OrFilter(
            [
                $pathMethodFilter,
                new PreflightFilter($path, $method),
            ]
        );
    }

    /**
     * @param Domain $domain
     * @param PortNumber $port
     * @return CombinedReplacer
     */
    private function createTransformer(
        Domain $domain,
        PortNumber $port
    ) {
        $domainReplacer = new DomainReplacer($domain);
        
        $portReplacer = new PortReplacer($port);
        
        return new CombinedReplacer([$domainReplacer, $portReplacer]);
    }
}
