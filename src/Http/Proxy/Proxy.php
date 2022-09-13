<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Proxy\Filter\AcceptFilter;
use CultuurNet\UDB3\Http\Proxy\Filter\MethodFilter;
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

final class Proxy implements RequestHandlerInterface
{
    private array $filters;

    private RequestTransformerInterface $requestTransformer;

    private ClientInterface $client;

    private function __construct(
        array $filters,
        Hostname $hostname,
        PortNumber $port,
        ClientInterface $client
    ) {
        $this->filters = $filters;
        $this->requestTransformer = $this->createTransformer($hostname, $port);
        $this->client = $client;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->filters as $filter) {
            if ($filter instanceof MethodFilter && !$filter->matches($request)) {
                throw ApiProblem::methodNotAllowed();
            }
            if ($filter instanceof AcceptFilter  && !$filter->matches($request)) {
                throw ApiProblem::notAcceptable();
            }
            if ($filter instanceof PathFilter && !$filter->matches($request)) {
                throw ApiProblem::urlNotFound();
            }
        }
        // Transform the request before re-sending it so we don't send the
        // exact same request and end up in an infinite loop.
        $psr7Request = $this->requestTransformer->transform($request);

        return $this->client->send(
            $psr7Request,
            [
                    'http_errors' => false,
            ]
        );
    }

    private function createTransformer(
        Hostname $hostname,
        PortNumber $port
    ): CombinedReplacer {
        $domainReplacer = new DomainReplacer($hostname);

        $portReplacer = new PortReplacer($port);

        return new CombinedReplacer([$domainReplacer, $portReplacer]);
    }

    private static function createSearchFilters(FilterPathRegex $path, string $method): array
    {
        return [
            new PathFilter($path),
            new MethodFilter(new StringLiteral($method)),
        ];
    }

    private static function createCdbXmlFilters(string $accept): array
    {
        return [
            new AcceptFilter(new StringLiteral($accept)),
            new MethodFilter(new StringLiteral('GET')),
        ];
    }

    public static function createForSearch(
        FilterPathRegex $path,
        string $method,
        Hostname $hostname,
        PortNumber $port,
        ClientInterface $client
    ): Proxy {
        return new self(
            self::createSearchFilters($path, $method),
            $hostname,
            $port,
            $client
        );
    }

    public static function createForCdbXml(
        string $accept,
        Hostname $hostname,
        PortNumber $port,
        ClientInterface $client
    ): Proxy {
        return new self(
            self::createCdbXmlFilters($accept),
            $hostname,
            $port,
            $client
        );
    }
}
