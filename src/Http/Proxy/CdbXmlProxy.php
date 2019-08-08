<?php

namespace CultuurNet\UDB3\Symfony\Proxy;

use CultuurNet\UDB3\Symfony\Proxy\Filter\AcceptFilter;
use CultuurNet\UDB3\Symfony\Proxy\Filter\AndFilter;
use CultuurNet\UDB3\Symfony\Proxy\Filter\MethodFilter;
use CultuurNet\UDB3\Symfony\Proxy\RequestTransformer\CombinedReplacer;
use CultuurNet\UDB3\Symfony\Proxy\RequestTransformer\DomainReplacer;
use CultuurNet\UDB3\Symfony\Proxy\RequestTransformer\PortReplacer;
use GuzzleHttp\ClientInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Domain;
use ValueObjects\Web\PortNumber;

class CdbXmlProxy extends Proxy
{
    /**
     * CdbXmlProxy constructor.
     * @param StringLiteral $accept
     * @param Domain $domain
     * @param PortNumber $port
     * @param DiactorosFactory $diactorosFactory
     * @param HttpFoundationFactory $httpFoundationFactory
     * @param ClientInterface $client
     */
    public function __construct(
        StringLiteral $accept,
        Domain $domain,
        PortNumber $port,
        DiactorosFactory $diactorosFactory,
        HttpFoundationFactory $httpFoundationFactory,
        ClientInterface $client
    ) {
        $cdbXmlFilter = $this->createFilter($accept);
        
        $requestTransformer = $this->createTransformer($domain, $port);

        parent::__construct(
            $cdbXmlFilter,
            $requestTransformer,
            $diactorosFactory,
            $httpFoundationFactory,
            $client
        );
    }

    /**
     * @param StringLiteral $accept
     * @return AndFilter
     */
    private function createFilter(StringLiteral $accept)
    {
        $acceptFilter = new AcceptFilter($accept);
        $methodFilter = new MethodFilter(new StringLiteral('GET'));

        return new AndFilter([$acceptFilter, $methodFilter]);
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
