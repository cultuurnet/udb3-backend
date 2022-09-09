<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\Proxy\Filter\AcceptFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\AndFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\FilterInterface;
use CultuurNet\UDB3\Http\Proxy\Filter\MethodFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\OrFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\PathFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\PreflightFilter;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\CombinedReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\DomainReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\PortReplacer;
use CultuurNet\UDB3\Http\Proxy\RequestTransformer\RequestTransformerInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use CultuurNet\UDB3\StringLiteral;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

final class Proxy implements RequestHandlerInterface
{
    private FilterInterface $filter;

    private RequestTransformerInterface $requestTransformer;

    private ClientInterface $client;

    public function __construct(
        FilterInterface $filter,
        Hostname $hostname,
        PortNumber $port,
        ClientInterface $client
    ) {
        $this->filter = $filter;
        $this->requestTransformer = $this->createTransformer($hostname, $port);
        $this->client = $client;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = null;

        if ($this->filter->matches($request)) {
            // Transform the request before re-sending it so we don't send the
            // exact same request and end up in an infinite loop.
            $psr7Request = $this->requestTransformer->transform($request);

            $response = $this->client->send(
                $psr7Request,
                [
                    'http_errors' => false,
                ]
            );

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

    private static function createSearchFilter(FilterPathRegex $path, string $method): FilterInterface
    {
        $pathMethodFilter = new AndFilter(
            [
                new PathFilter($path),
                new MethodFilter(new StringLiteral($method)),
            ]
        );

        return new OrFilter(
            [
                $pathMethodFilter,
                new PreflightFilter($path, new StringLiteral($method)),
            ]
        );
    }

    private static function createCdbXmlFilter(string $accept): AndFilter
    {
        $acceptFilter = new AcceptFilter(new StringLiteral($accept));
        $methodFilter = new MethodFilter(new StringLiteral('GET'));

        return new AndFilter([$acceptFilter, $methodFilter]);
    }

    public static function createWithSearchFilter(
        FilterPathRegex $path,
        string $method,
        Hostname $hostname,
        PortNumber $port,
        ClientInterface $client
    ): Proxy {
        return new self(
            self::createSearchFilter($path, $method),
            $hostname,
            $port,
            $client
        );
    }

    public static function createWithCdbXmlFilter(
        string $accept,
        Hostname $hostname,
        PortNumber $port,
        ClientInterface $client
    ): Proxy {
        return new self(
            self::createCdbXmlFilter($accept),
            $hostname,
            $port,
            $client
        );
    }
}
