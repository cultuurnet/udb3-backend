<?php

declare(strict_types=1);

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
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use GuzzleHttp\ClientInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use ValueObjects\StringLiteral\StringLiteral;

class FilterPathMethodProxy extends Proxy
{
    public function __construct(
        FilterPathRegex $path,
        StringLiteral $method,
        Hostname $hostname,
        PortNumber $port,
        DiactorosFactory $diactorosFactory,
        HttpFoundationFactory $httpFoundationFactory,
        ClientInterface $client
    ) {
        parent::__construct(
            $this->createFilter($path, $method),
            $this->createTransformer($hostname, $port),
            $diactorosFactory,
            $httpFoundationFactory,
            $client
        );
    }

    /**
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
