<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\Proxy\Filter\AcceptFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\AndFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\MethodFilter;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\CombinedReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\DomainReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\PortReplacer;
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use GuzzleHttp\ClientInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use ValueObjects\StringLiteral\StringLiteral;

class CdbXmlProxy extends Proxy
{
    /**
     * CdbXmlProxy constructor.
     */
    public function __construct(
        StringLiteral $accept,
        Hostname $hostname,
        PortNumber $port,
        DiactorosFactory $diactorosFactory,
        HttpFoundationFactory $httpFoundationFactory,
        ClientInterface $client
    ) {
        $cdbXmlFilter = $this->createFilter($accept);

        $requestTransformer = $this->createTransformer($hostname, $port);

        parent::__construct(
            $cdbXmlFilter,
            $requestTransformer,
            $diactorosFactory,
            $httpFoundationFactory,
            $client
        );
    }

    /**
     * @return AndFilter
     */
    private function createFilter(StringLiteral $accept)
    {
        $acceptFilter = new AcceptFilter($accept);
        $methodFilter = new MethodFilter(new StringLiteral('GET'));

        return new AndFilter([$acceptFilter, $methodFilter]);
    }

    /**
     * @return CombinedReplacer
     */
    private function createTransformer(
        Hostname $hostname,
        PortNumber $port
    ) {
        $domainReplacer = new DomainReplacer($hostname);

        $portReplacer = new PortReplacer($port);

        return new CombinedReplacer([$domainReplacer, $portReplacer]);
    }
}
